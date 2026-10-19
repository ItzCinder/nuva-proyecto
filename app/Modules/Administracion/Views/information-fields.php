<label class="environment-field" for="environment-name">
    <span>Nombre del entorno <small class="environment-muted">· Obligatorio</small>
    </span>
    <input id="environment-name" name="name" value="<?= nuva_escape($_SERVER['REQUEST_METHOD'] === 'POST' ? nuva_form_value('name') : ($workspace['name'] ?? '')) ?>" required maxlength="120" placeholder="Ej.: Club Deportivo del Sur" autocomplete="organization">
</label>
<div class="environment-photo-field">
    <?php if (!empty($workspace['picture'])): ?>
        <span class="environment-mark environment-mark--preview">
            <img class="environment-picture" src="<?= nuva_escape($workspace['picture']) ?>" alt="Foto actual del entorno">
        </span>
    <?php endif; ?>
    <label class="environment-field" for="environment-picture">Foto del entorno (opcional)
        <input id="environment-picture" type="file" name="picture" accept="image/jpeg,image/png,image/webp" aria-describedby="environment-picture-help">
        <span id="environment-picture-help" class="environment-muted">JPG, PNG o WebP de hasta 2 MB. Se muestra en las tarjetas y la ficha del entorno. Usa una imagen que tengas permiso para compartir.</span>
    </label>
    <?php if (!empty($workspace['picture'])): ?>
        <label class="environment-check"><input type="checkbox" name="remove_picture" value="1"> Quitar la foto actual</label>
    <?php endif; ?>
</div>
<label class="environment-field" for="environment-description">Descripción
    <textarea id="environment-description" name="description" maxlength="500" rows="4" placeholder="Cuenta qué representa tu entorno y qué actividades organiza."><?= nuva_escape($_SERVER['REQUEST_METHOD'] === 'POST' ? nuva_form_value('description') : ($workspace['description'] ?? '')) ?></textarea>
    <span class="environment-muted">Hasta 500 caracteres.</span>
</label>
