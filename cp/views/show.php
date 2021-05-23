<table>
	<thead>
		<tr>
			<th>#</th>
			<?php foreach ($fields as $colName => $field): ?>
				<?php if (!isset($field['hide'])): ?>
					<th><?= ucwords(str_replace('_', ' ', $colName)); ?></th>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if (isset($table[0]['created_at'])): ?>
					<th>Created At</th>
			<?php endif; ?>
			<?php if (isset($table[0]['updated_at'])): ?>
					<th>Updated At</th>
			<?php endif; ?>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($table as $row): ?>
			<tr>
				<?php foreach ($row as $colName => $col): ?>
					<?php if (!isset($fields[$colName]['hide'])): ?>
						<td class="<?= $colName; ?>"><?= $col; ?></td>
					<?php endif; ?>
				<?php endforeach; ?>
				<td class="edit">
					<a href="<?= '/cp/' . $method . '/' . $row['id'] . '/edit'; ?>">
						<svg viewBox="0 0 32 32">
							<path d="M27 0c2.761 0 5 2.239 5 5 0 1.126-0.372 2.164-1 3l-2 2-7-7 2-2c0.836-0.628 1.874-1 3-1zM2 23l-2 9 9-2 18.5-18.5-7-7-18.5 18.5zM22.362 11.362l-14 14-1.724-1.724 14-14 1.724 1.724z"></path>
						</svg>
					</a>
				</td>
				<td class="delete">
					<a href="<?= '/cp/' . $method . '/' . $row['id'] . '/delete'; ?>">
						<svg viewBox="0 0 32 32">
							<path d="M4 10v20c0 1.1 0.9 2 2 2h18c1.1 0 2-0.9 2-2v-20h-22zM10 28h-2v-14h2v14zM14 28h-2v-14h2v14zM18 28h-2v-14h2v14zM22 28h-2v-14h2v14z"></path>
							<path d="M26.5 4h-6.5v-2.5c0-0.825-0.675-1.5-1.5-1.5h-7c-0.825 0-1.5 0.675-1.5 1.5v2.5h-6.5c-0.825 0-1.5 0.675-1.5 1.5v2.5h26v-2.5c0-0.825-0.675-1.5-1.5-1.5zM18 4h-6v-1.975h6v1.975z"></path>
						</svg>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
