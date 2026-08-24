<?php if (!empty($nodejsPanelVisible)) { ?>
	<div class="u-mb20">
		<h2 class="u-mb10"><?= tohtml(_('Node.js Application')) ?></h2>
		<div class="inline-alert <?= tohtml($nodejsStatusClass) ?> u-mb10" role="alert">
			<i class="fas fa-circle-info"></i>
			<p><?= tohtml(_('Status')) ?>: <?= tohtml($nodejsStatusLabel) ?></p>
		</div>

		<?php if (!empty($nodejsStatusError)) { ?>
			<div class="inline-alert inline-alert-danger u-mb10" role="alert">
				<i class="fas fa-circle-exclamation"></i>
				<p><?= tohtml($nodejsStatusError) ?></p>
			</div>
		<?php } ?>

		<ul class="values-list u-mb15">
			<input type="hidden" name="nodejs_action" id="nodejs_action_field" value="">
			<?php if (!empty($nodejsStatusData['node_version'])) { ?>
				<li class="values-list-item">
					<span class="values-list-label"><?= tohtml(_('Node.js')) ?></span>
					<span class="values-list-value"><?= tohtml($nodejsStatusData['node_version']) ?></span>
				</li>
			<?php } ?>
			<?php if (!empty($nodejsStatusData['port'])) { ?>
				<li class="values-list-item">
					<span class="values-list-label"><?= tohtml(_('Port')) ?></span>
					<span class="values-list-value"><?= tohtml($nodejsStatusData['port']) ?></span>
				</li>
			<?php } ?>
			<?php if (!empty($nodejsStatusData['pm2_name'])) { ?>
				<li class="values-list-item">
					<span class="values-list-label"><?= tohtml(_('PM2 Name')) ?></span>
					<span class="values-list-value"><?= tohtml($nodejsStatusData['pm2_name']) ?></span>
				</li>
			<?php } ?>
			<?php if (!empty($nodejsStatusData['uptime'])) { ?>
				<li class="values-list-item">
					<span class="values-list-label"><?= tohtml(_('Uptime')) ?></span>
					<span class="values-list-value"><?= tohtml($nodejsStatusData['uptime']) ?></span>
				</li>
			<?php } ?>
			<?php if (isset($nodejsStatusData['restart_count']) && $nodejsStatusData['restart_count'] !== '') { ?>
				<li class="values-list-item">
					<span class="values-list-label"><?= tohtml(_('Restart Count')) ?></span>
					<span class="values-list-value"><?= tohtml($nodejsStatusData['restart_count']) ?></span>
				</li>
			<?php } ?>
		</ul>

		<div class="toolbar-buttons">
			<button type="submit" class="button button-secondary" formnovalidate onclick="document.getElementById('nodejs_action_field').value='start'" <?php if (!$nodejsCanStart) echo 'disabled'; ?>>
				<i class="fas fa-play icon-green"></i><?= tohtml(_('Start')) ?>
			</button>
			<button type="submit" class="button button-secondary" formnovalidate onclick="document.getElementById('nodejs_action_field').value='stop'" <?php if (!$nodejsCanStop) echo 'disabled'; ?>>
				<i class="fas fa-stop icon-red"></i><?= tohtml(_('Stop')) ?>
			</button>
			<button type="submit" class="button button-secondary" formnovalidate onclick="document.getElementById('nodejs_action_field').value='restart'" <?php if (!$nodejsCanRestart) echo 'disabled'; ?>>
				<i class="fas fa-rotate-right icon-blue"></i><?= tohtml(_('Restart')) ?>
			</button>
		</div>

		<div class="u-mt15 u-mb10">
			<label for="nodejs_log_type" class="form-label"><?= tohtml(_('Logs')) ?></label>
			<div class="u-flex u-gap10" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
				<div>
					<select class="form-select" name="nodejs_log_type" id="nodejs_log_type">
						<option value="out" <?php if ($nodejsLogType === 'out') echo 'selected'; ?>><?= tohtml(_('Output')) ?></option>
						<option value="err" <?php if ($nodejsLogType === 'err') echo 'selected'; ?>><?= tohtml(_('Error')) ?></option>
					</select>
				</div>
				<div>
					<input type="number" class="form-control" name="nodejs_log_lines" min="1" max="200" value="<?= tohtml((string) $nodejsLogLines) ?>">
				</div>
				<div>
					<button type="submit" class="button button-secondary" formnovalidate onclick="document.getElementById('nodejs_action_field').value='logs'">
						<i class="fas fa-file-lines icon-purple"></i><?= tohtml(_('View logs')) ?>
					</button>
				</div>
			</div>
		</div>

		<?php if ($nodejsLogOutput !== '') { ?>
			<div class="u-mt10">
				<textarea class="form-control u-min-height100 u-console" readonly><?= tohtml($nodejsLogOutput) ?></textarea>
			</div>
		<?php } ?>
	</div>
<?php } ?>
