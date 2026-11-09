#!/bin/bash
set -euo pipefail

# Load only the helpers; Hestia itself is not required for these tests.
source <(sed -n '/^nodejs_load_context()/,$p' "$(dirname "$0")/../bin/nodejs-app-common")

user=demo
NODE_VERSION=20
PORT=3000
PM2_NAME=nodejs-example
APP_DIR=/home/demo/web/example.com/private/nodeapp
START_SCRIPT='npm start'
process=''
system_version=v24.11.0

sudo() {
	[[ "$*" == '-u demo -H env PATH=/usr/local/bin:/usr/bin:/bin node --version' ]]
	if [ -n "$system_version" ]; then
		echo "$system_version"
	else
		return 1
	fi
}

nodejs_pm2_process() {
	echo "$process"
}

assert_version() {
	local expected=$1 json
	json="$(nodejs_print_status json)"
	[[ "$(jq -r '.node_version' <<< "$json")" == "$expected" ]]
	[[ "$(nodejs_print_status text | grep '^NODE_VERSION=')" == "NODE_VERSION=$expected" ]]
}

# The actual PM2 runtime takes precedence over both config and system Node.
process='{"pid":123,"pm2_env":{"status":"online","node_version":"24.10.0"}}'
assert_version 24.10.0

# Shell wrappers may lack PM2 Node.js metadata.
process='{"pm2_env":{"status":"online","node_version":"N/A"}}'
assert_version v24.11.0

# Missing and stopped processes still expose the available runtime.
process=''
assert_version v24.11.0
process='{"pm2_env":{"status":"stopped","node_version":"24.10.0"}}'
assert_version 24.10.0

# Do not misrepresent the configured version as an actual runtime.
process=''
system_version=''
assert_version ''
system_version='invalid output'
assert_version ''

echo 'Node.js status tests passed.'
