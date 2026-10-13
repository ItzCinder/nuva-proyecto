<label class="environment-field" for="environment-name"><span>Nombre del entorno <small class="environment-muted">· Obligatorio</small></span>
    <input id="environment-name" name="name" value="<?= $escape($_SERVER['REQUEST_METHOD'] === 'POST' ? $input('name') : ($workspace['name'] ?? '')) ?>" required maxlength="120" placeholder="Ej.: Club Deportivo del Sur" autocomplete="organization">
</label>
<label class="environment-field" for="environment-description">Descripción
    <textarea id="environment-description" name="description" maxlength="500" rows="4" placeholder="Cuenta qué representa tu entorno y qué actividades organiza."><?= $escape($_SERVER['REQUEST_METHOD'] === 'POST' ? $input('description') : ($workspace['description'] ?? '')) ?></textarea>
    <span class="environment-muted">Hasta 500 caracteres.</span>
</label>
