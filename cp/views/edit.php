<form action="/" method="POST">
	<table>
		<?php foreach ($fields as $name => $field): ?>
			<tr>
				<td><?= ucwords($name); ?></td>
				<td>
					<?php if ($field['field'] == 'text'): ?>
						<input type="text" name="<?= $name; ?>" value="<?= $table[$name]; ?>">
					<?php elseif ($field['field'] == 'number'): ?>
						<input type="number" name="<?= $name; ?>" value="<?= $table[$name]; ?>">
					<?php elseif ($field['field'] == 'boolean'): ?>
						<input type="checkbox" name="<?= $name; ?>">
					<?php elseif ($field['field'] == 'select'): ?>
						<select name="<?= $name; ?>">
							<option>-- Select --</option>
						</select>
					<?php elseif ($field['field'] == 'file'): ?>
						<input type="file" name="<?= $name; ?>">
					<?php elseif ($field['field'] == 'multi'): ?>
						<select name="<?= $name; ?>" multiple="multiple" size="5">
							<option>-- Select --</option>
						</select>
					<?php elseif ($field['field'] == 'textarea'): ?>
						<textarea name="<?= $name; ?>"></textarea>
					<?php elseif ($field['field'] == 'editor'): ?>
						<textarea name="<?= $name; ?>" class="rich-text"></textarea>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<input type="submit" value="Save">
</form>