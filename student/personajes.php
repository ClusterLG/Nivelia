<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/personajes.php';
requireLogin();

$user = currentUser();
$personajes = getPersonajes();
$baseUrl = getBaseUrl();

$pageTitle = 'Club de Amigos - Fauna Venezolana';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
  
  <!-- Banner del Club de Personajes -->
  <div style="background: linear-gradient(135deg, #0284c7, #4f46e5); color: #ffffff; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-md); position: relative; overflow: hidden;">
    <div style="position: absolute; right: -20px; bottom: -20px; font-size: 8rem; opacity: 0.15; pointer-events: none;">🐾</div>
    <div style="max-width: 680px; position: relative; z-index: 1;">
      <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">
        Tus Compañeros de Aprendizaje
      </span>
      <h1 style="color: #ffffff; font-size: 2rem; margin: 0.5rem 0 0.5rem 0;">
        ¡Conoce al Escuadrón de la Fauna Venezolana! 🦜🐾
      </h1>
      <p style="color: #e0f2fe; font-size: 1.05rem; line-height: 1.5;">
        Cada una de tus materias escolares cuenta con un tutor especial inspirado en los animales más asombrosos de nuestra tierra. ¡Haz clic en cualquiera de ellos para escucharlo y descubrir sus consejos!
      </p>
    </div>
  </div>

  <!-- Escenario Interactivo de Diálogo en Vivo -->
  <div id="interactiveDialogueBox">
    <?= renderMascotDialogue('guacamaya', '¡Hola, ' . htmlspecialchars($user['name']) . '! Soy Maya la Guacamaya. Te acompañaré en tus lecturas para descubrir historias fascinantes sobre Venezuela y el mundo. ¡Haz clic en cualquiera de mis amigos abajo para conocerlos!', 'Maya la Guacamaya te saluda:', 'Lectura y Lengua') ?>
  </div>

  <!-- Galería de los 6 Personajes Pixar -->
  <div style="margin-top: 2.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
      <h2 style="font-size: 1.4rem;">🌟 Los 6 Tutores del Portal</h2>
      <span style="font-size: 0.88rem; color: var(--text-muted); font-weight: 600;">Estilo Pixar 3D</span>
    </div>

    <div class="characters-grid">
      <?php foreach ($personajes as $slug => $char): ?>
        <div class="character-card" 
             style="--char-color: <?= htmlspecialchars($char['color_tema']) ?>; cursor: pointer;"
             onclick="selectCharacter('<?= htmlspecialchars($slug) ?>')"
             id="card-<?= htmlspecialchars($slug) ?>">
          
          <img src="<?= $baseUrl ?>assets/images/personajes/<?= htmlspecialchars($char['archivo_render']) ?>" 
               alt="<?= htmlspecialchars($char['nombre']) ?>" 
               class="character-card-img"
               loading="lazy">

          <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.25rem; color: var(--text-primary);">
            <?= htmlspecialchars($char['nombre']) ?>
          </h3>
          
          <span style="font-size: 0.8rem; font-weight: 700; color: <?= htmlspecialchars($char['color_tema']) ?>; background: rgba(0,0,0,0.03); padding: 0.2rem 0.6rem; border-radius: var(--radius-full); margin-bottom: 0.75rem; border: 1px solid currentColor;">
            <?= htmlspecialchars($char['materia_principal']) ?>
          </span>

          <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 0.75rem; line-height: 1.4;">
            <strong>Especie:</strong> <?= htmlspecialchars($char['especie']) ?><br>
            <strong>Hábitat:</strong> <?= htmlspecialchars($char['habitat']) ?>
          </p>

          <div style="background: #f8fafc; border: 1px dashed var(--border); border-radius: var(--radius-md); padding: 0.6rem 0.75rem; font-size: 0.82rem; font-style: italic; color: var(--text-secondary); margin-bottom: 1rem; width: 100%;">
            "<?= htmlspecialchars($char['lema']) ?>"
          </div>

          <button type="button" class="btn btn-sm btn-block" style="background: <?= htmlspecialchars($char['color_tema']) ?>; color: #ffffff; border: none; font-weight: 700;">
            💬 Hablar con <?= explode(' ', $char['nombre'])[0] ?>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Datos JSON para interacción en el cliente -->
<script>
const charactersData = <?= json_encode($personajes, JSON_UNESCAPED_UNICODE) ?>;
const studentName = <?= json_encode($user['name']) ?>;
const baseUrl = <?= json_encode($baseUrl) ?>;

function selectCharacter(slug) {
  const char = charactersData[slug];
  if (!char) return;

  SoundFx.click();

  // Scroll suave al cuadro de diálogo
  const box = document.getElementById('interactiveDialogueBox');
  if (box) {
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Armar diálogo dinámico
    const imagePath = baseUrl + 'assets/images/personajes/' + char.archivo_render;
    const dialogText = char.dialogos.bienvenida.replace('campeón', studentName);

    box.innerHTML = `
      <div class="mascot-dialogue-card" style="--mascot-accent: ${char.color_tema}; animation: popIn 0.35s ease;">
        <div class="mascot-avatar-wrap">
          <div class="mascot-glow-bg" style="background: radial-gradient(circle, ${char.color_tema} 0%, rgba(255,255,255,0) 70%);"></div>
          <img src="${imagePath}" alt="${char.nombre}" class="mascot-avatar-img">
          <div class="mascot-badge-tag">${char.especie}</div>
        </div>

        <div class="mascot-speech-bubble" style="border-color: ${char.color_tema};">
          <div class="mascot-speech-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <span class="mascot-name">${char.nombre}</span>
              <span class="mascot-subject-pill" style="color: ${char.color_tema}; border-color: ${char.color_tema};">${char.materia_principal}</span>
            </div>
            <button type="button" class="mascot-sound-btn" onclick="SoundFx.success()" title="Escuchar sonido">
              <span>🔊</span>
            </button>
          </div>

          <div class="mascot-speech-content">
            ${dialogText}
            <div style="margin-top: 0.6rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0; font-size: 0.9rem; color: #475569;">
              💡 <strong>Consejo escolar:</strong> "${char.dialogos.consejo || char.dialogos.lectura || ''}"
            </div>
          </div>

          <div class="mascot-speech-footer">
            <em>"${char.lema}"</em>
          </div>
        </div>
      </div>
    `;
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
