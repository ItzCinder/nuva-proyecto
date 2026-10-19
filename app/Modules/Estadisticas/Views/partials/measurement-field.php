<label class="environment-field">
    <?= nuva_escape($label) ?> (<?= nuva_escape($unit) ?>)
    <input type="number" inputmode="decimal" name="<?= nuva_escape($key) ?>" step="0.00000001" min="<?= $minimum ?>" max="<?= $maximum ?>" value="<?= nuva_escape(nuva_trial_value($values, $key)) ?>" placeholder="<?= nuva_escape($placeholder) ?>" <?php if ($required) echo 'data-required'; ?>>
    <?php if ($hint !== ''): ?>
    <small><?= nuva_escape($hint) ?></small>
    <?php endif; ?>
</label>
