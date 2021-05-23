<form action="/" method="POST">
	<table>
		<?php foreach ($fields as $name => $field): ?>
			<tr>
				<td><?= ucwords(str_replace('_', ' ', $name)); ?></td>
				<td>
					<?php if ($field['field'] == 'text'): ?>
						<input type="text" name="<?= $name; ?>" value="<?= $table[$name]; ?>">
					<?php elseif ($field['field'] == 'number'): ?>
						<input type="number" name="<?= $name; ?>" value="<?= $table[$name]; ?>">
					<?php elseif ($field['field'] == 'boolean'): ?>
						<input type="checkbox" name="<?= $name; ?>" <?= $table[$name] == 1 ? 'checked="checked"' : ''; ?>>
					<?php elseif ($field['field'] == 'select'): ?>
						<select name="<?= $name; ?>">
							<option>-- Select --</option>
						</select>
					<?php elseif ($field['field'] == 'file'): ?>
						<input type="file" name="<?= $name; ?>" value="<?= $table[$name]; ?>">
					<?php elseif ($field['field'] == 'multi'): ?>
						<select name="<?= $name; ?>" multiple="multiple" size="5">
							<option>-- Select --</option>
						</select>
					<?php elseif ($field['field'] == 'textarea'): ?>
						<textarea name="<?= $name; ?>"><?= $table[$name]; ?></textarea>
					<?php elseif ($field['field'] == 'editor'): ?>
						<textarea name="<?= $name; ?>" class="rich-text"><?= $table[$name]; ?></textarea>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php if (isset($table['created_at'])): ?>
			<tr>
				<td>Created At</td>
				<td><?= $table['created_at']; ?></td>
			</tr>
		<?php endif; ?>
		<?php if (isset($table['updated_at'])): ?>
			<tr>
				<td>Updated At</td>
				<td><?= $table['updated_at']; ?></td>
			</tr>
		<?php endif; ?>
	</table>
	<button type="submit">
		<svg viewBox="0 0 32 32">
			<path d="M28 0h-28v32h32v-28l-4-4zM16 4h4v8h-4v-8zM28 28h-24v-24h2v10h18v-10h2.343l1.657 1.657v22.343z"></path>
		</svg>
	</button>
</form>