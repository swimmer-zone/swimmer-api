<table>
	<thead>
		<tr>
			<?php foreach (array_keys($table[0]) as $colName): ?>
				<?php if (!isset($fields[$colName]['hide'])): ?>
					<th><?= ucwords($name); ?></th>
				<?php endif; ?>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($table as $row): ?>
			<tr>
				<?php foreach ($row as $colName => $col): ?>
					<?php if (!isset($fields[$colName]['hide'])): ?>
						<td><?= $col; ?></td>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
