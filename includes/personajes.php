<?php
/**
 * Sistema de Personajes Animados - Fauna Venezolana (Estilo Pixar)
 * Inspirado en Education.com: Mascotas que interactúan con cuadros de diálogo
 */

function getPersonajes() {
    static $personajes = null;
    if ($personajes !== null) {
        return $personajes;
    }

    $basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'personajes';
    $folders = ['guacamaya', 'iguana', 'periquito', 'tonina', 'nutria_gigante', 'chiguire'];
    $personajes = [];

    foreach ($folders as $f) {
        $jsonPath = $basePath . DIRECTORY_SEPARATOR . $f . DIRECTORY_SEPARATOR . 'perfil.json';
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if ($data) {
                $personajes[$f] = $data;
            }
        }
    }

    return $personajes;
}

function getPersonajeBySubject($subjectSlug) {
    $map = [
        'lectura' => 'guacamaya',
        'riesgo_laguaira' => 'iguana',
        'algebra' => 'periquito',
        'biologia' => 'tonina',
        'matematicas' => 'nutria_gigante',
        'ciencias' => 'nutria_gigante',
        'ciudadania' => 'chiguire',
        'historia' => 'chiguire',
    ];

    $slug = $map[strtolower($subjectSlug)] ?? 'chiguire';
    $todos = getPersonajes();
    return $todos[$slug] ?? null;
}

function getPersonajeRandom() {
    $todos = getPersonajes();
    if (empty($todos)) return null;
    $keys = array_keys($todos);
    return $todos[$keys[array_rand($keys)]];
}

/**
 * Renderizar Componente Visual de Personaje con Cuadro de Diálogo estilo Education.com
 */
function renderMascotDialogue($charSlug, $dialogueText, $customTitle = '', $badgeText = '') {
    $todos = getPersonajes();
    $char = $todos[$charSlug] ?? null;
    if (!$char) return '';

    $baseUrl = getBaseUrl();
    $imageWebPath = $baseUrl . 'assets/images/personajes/' . $char['archivo_render'];
    $title = $customTitle ?: $char['nombre'];
    $badge = $badgeText ?: $char['materia_principal'];

    ob_start();
    ?>
    <div class="mascot-dialogue-card" style="--mascot-accent: <?= htmlspecialchars($char['color_tema']) ?>;">
      <div class="mascot-avatar-wrap">
        <div class="mascot-glow-bg"></div>
        <img src="<?= htmlspecialchars($imageWebPath) ?>" 
             alt="<?= htmlspecialchars($char['nombre']) ?>" 
             class="mascot-avatar-img"
             loading="lazy">
        <div class="mascot-badge-tag"><?= htmlspecialchars($char['especie']) ?></div>
      </div>

      <div class="mascot-speech-bubble">
        <div class="mascot-speech-header">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span class="mascot-name"><?= htmlspecialchars($title) ?></span>
            <span class="mascot-subject-pill"><?= htmlspecialchars($badge) ?></span>
          </div>
          <button type="button" class="mascot-sound-btn" onclick="SoundFx.click()" title="Escuchar">
            <span>🔊</span>
          </button>
        </div>

        <div class="mascot-speech-content">
          <?= nl2br(htmlspecialchars($dialogueText)) ?>
        </div>

        <div class="mascot-speech-footer">
          <em>"<?= htmlspecialchars($char['lema']) ?>"</em>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Obtener configuración visual de hábitat para el fondo temático claro
 */
function getMascotHabitatTheme($charSlug) {
    $themes = [
        'guacamaya' => [
            'id' => 'guacamaya',
            'nombre_habitat' => 'Cordillera de la Costa y El Ávila',
            'top_gradient' => 'linear-gradient(180deg, #dbeafe 0%, #eff6ff 45%, #ecfdf5 100%)',
            'bottom_gradient' => 'linear-gradient(180deg, #ecfdf5 0%, #dcfce7 50%, #bbf7d0 100%)',
            'bg_tint' => '#f0f9ff',
            'accent_color' => '#ef4444',
            'light_accent' => '#fee2e2',
            'ambient_icon' => '🦜',
            'icons' => ['🦜', '🌿', '🌺', '☁️', '🍃'],
            'top_frame_label' => '🌄 Cima del Waraira Repano',
            'bottom_frame_label' => '🌳 Bosque Nublado Tropical',
            'elements' => 'tropical_canopy'
        ],
        'iguana' => [
            'id' => 'iguana',
            'nombre_habitat' => 'Litoral Central y Costa de La Guaira',
            'top_gradient' => 'linear-gradient(180deg, #e0f2fe 0%, #f0fdf4 45%, #ecfeff 100%)',
            'bottom_gradient' => 'linear-gradient(180deg, #ecfeff 0%, #fefce8 50%, #fef08a 100%)',
            'bg_tint' => '#ecfeff',
            'accent_color' => '#10b981',
            'light_accent' => '#d1fae5',
            'ambient_icon' => '🦎',
            'icons' => ['🦎', '🌊', '☀️', '🌴', '🐚'],
            'top_frame_label' => '⛰️ Laderas de La Guaira',
            'bottom_frame_label' => '🏖️ Orilla del Mar Caribe',
            'elements' => 'caribbean_coast'
        ],
        'periquito' => [
            'id' => 'periquito',
            'nombre_habitat' => 'Arboledas y Jardines Silvestres',
            'top_gradient' => 'linear-gradient(180deg, #fefce8 0%, #f7fee7 45%, #ecfdf5 100%)',
            'bottom_gradient' => 'linear-gradient(180deg, #ecfdf5 0%, #d9f99d 50%, #bef264 100%)',
            'bg_tint' => '#f7fee7',
            'accent_color' => '#84cc16',
            'light_accent' => '#ecfccb',
            'ambient_icon' => '🐤',
            'icons' => ['🐤', '🌻', '🍃', '☀️', '🌱'],
            'top_frame_label' => '🌤️ Copas de Árboles Floridos',
            'bottom_frame_label' => '🌾 Campo y Sabana Verde',
            'elements' => 'sunny_grove'
        ],
        'tonina' => [
            'id' => 'tonina',
            'nombre_habitat' => 'Aguas Dulces del Río Orinoco',
            'top_gradient' => 'linear-gradient(180deg, #fdf2f8 0%, #eff6ff 45%, #e0f2fe 100%)',
            'bottom_gradient' => 'linear-gradient(180deg, #e0f2fe 0%, #cffafe 50%, #ede9fe 100%)',
            'bg_tint' => '#fdf2f8',
            'accent_color' => '#ec4899',
            'light_accent' => '#fce7f3',
            'ambient_icon' => '🐬',
            'icons' => ['🐬', '💧', '🪷', '🫧', '🌊'],
            'top_frame_label' => '🌅 Superficie del Río Orinoco',
            'bottom_frame_label' => '🪷 Lecho Fluvial con Nenúfares',
            'elements' => 'river_waters'
        ],
        'nutria_gigante' => [
            'id' => 'nutria_gigante',
            'nombre_habitat' => 'Riberas y Caudales de Guayana',
            'top_gradient' => 'linear-gradient(180deg, #f0fdfa 0%, #e0f2fe 45%, #ccfbf1 100%)',
            'bottom_gradient' => 'linear-gradient(180deg, #ccfbf1 0%, #99f6e4 50%, #e0e7ff 100%)',
            'bg_tint' => '#f0fdfa',
            'accent_color' => '#0891b2',
            'light_accent' => '#cffafe',
            'ambient_icon' => '🦦',
            'icons' => ['🦦', '🌾', '💎', '🐟', '💦'],
            'top_frame_label' => '🏔️ Cañón y Niebla Matutina',
            'bottom_frame_label' => '🪨 Ribera Cristalina con Juncos',
            'elements' => 'clear_creek'
        ],
        'chiguire' => [
            'id' => 'chiguire',
            'nombre_habitat' => 'Esteros de Camaguán y Llanos',
            'top_gradient' => 'linear-gradient(180deg, #fffbeb 0%, #fef3c7 45%, #fef9c3 100%)',
            'bottom_gradient' => 'linear-gradient(180deg, #fef9c3 0%, #d9f99d 50%, #ecfdf5 100%)',
            'bg_tint' => '#fefce8',
            'accent_color' => '#d97706',
            'light_accent' => '#fef3c7',
            'ambient_icon' => '🌾',
            'icons' => ['🌾', '🦩', '🌅', '🌴', '✨'],
            'top_frame_label' => '🌄 Amanecer Dorado de los Llanos',
            'bottom_frame_label' => '🌿 Estero Calmo y Pastizales',
            'elements' => 'llanos_plains'
        ]
    ];

    return $themes[strtolower($charSlug)] ?? $themes['chiguire'];
}

/**
 * Renderizar el Fondo Parallax Scroll-Driven Temático de la Fauna Venezolana
 */
function renderHabitatParallaxBackground($charSlug) {
    $theme = getMascotHabitatTheme($charSlug);
    $accent = htmlspecialchars($theme['accent_color']);
    $topLabel = htmlspecialchars($theme['top_frame_label']);
    $bottomLabel = htmlspecialchars($theme['bottom_frame_label']);
    $habitatName = htmlspecialchars($theme['nombre_habitat']);

    ob_start();
    ?>
    <!-- Contenedor Fijo de Fondo Parallax del Hábitat Venezolano -->
    <div id="habitatParallaxContainer" 
         class="habitat-parallax-container theme-<?= htmlspecialchars($theme['id']) ?>" 
         style="--habitat-accent: <?= $accent ?>; --habitat-top: <?= $theme['top_gradient'] ?>; --habitat-bottom: <?= $theme['bottom_gradient'] ?>;">
      
      <!-- Capa 1: Gradiente base de cielo/atmósfera animado por scroll -->
      <div class="habitat-gradient-layer" id="habitatGradientLayer"></div>

      <!-- Capa 2: Elementos paisajísticos vectoriales suaves (Top frame: Cima / Nubes) -->
      <div class="habitat-scenery-sky" id="habitatScenerySky">
        <svg viewBox="0 0 1440 320" preserveAspectRatio="none" class="habitat-svg-wave top-wave">
          <path fill="currentColor" fill-opacity="0.25" d="M0,96L48,112C96,128,192,160,288,154.7C384,149,480,107,576,101.3C672,96,768,128,864,154.7C960,181,1056,203,1152,186.7C1248,171,1344,117,1392,90.7L1440,64L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path>
        </svg>
        <div class="habitat-ambient-cloud cloud-1">☁️</div>
        <div class="habitat-ambient-cloud cloud-2">☁️</div>
        <div class="habitat-sun-glow"></div>
      </div>

      <!-- Capa 3: Paisaje intermedio que se desplaza en parallax -->
      <div class="habitat-scenery-mid" id="habitatSceneryMid">
        <div class="habitat-floating-flora flora-left"><?= $theme['icons'][1] ?? '🌿' ?></div>
        <div class="habitat-floating-flora flora-right"><?= $theme['icons'][2] ?? '🍃' ?></div>
        <div class="habitat-floating-flora flora-center"><?= $theme['icons'][0] ?? '✨' ?></div>
      </div>

      <!-- Capa 4: Elementos de suelo / ribera / vegetación base (Último fotograma al fondo de página) -->
      <div class="habitat-scenery-ground" id="habitatSceneryGround">
        <svg viewBox="0 0 1440 320" preserveAspectRatio="none" class="habitat-svg-wave bottom-wave">
          <path fill="currentColor" fill-opacity="0.3" d="M0,224L48,208C96,192,192,160,288,165.3C384,171,480,213,576,218.7C672,224,768,192,864,165.3C960,139,1056,117,1152,128C1248,139,1344,181,1392,202.7L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
      </div>

      <!-- Indicador sutil de hábitat activo -->
      <div class="habitat-badge-indicator" title="Hábitat natural del tutor">
        <span class="indicator-icon"><?= $theme['ambient_icon'] ?></span>
        <span class="indicator-text">Hábitat: <?= $habitatName ?></span>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renderizar Compañero Tutor Interactivo al Lateral de la Tarjeta de Lectura
 */
function renderSideMascotCompanion($charSlug, $studentName, $dialogueList = []) {
    $todos = getPersonajes();
    $char = $todos[$charSlug] ?? null;
    if (!$char) return '';

    $baseUrl = getBaseUrl();
    $imageWebPath = $baseUrl . 'assets/images/personajes/' . $char['archivo_render'];
    
    $tips = !empty($dialogueList) ? $dialogueList : [
        $char['dialogos']['lectura'] ?? $char['dialogos']['bienvenida'],
        $char['dialogos']['consejo'] ?? '¡Tómate tu tiempo! Imagina lo que lees en tu mente.',
        '¿Sabías qué? ' . $char['lema'],
        '¡Sigue adelante, ' . $studentName . '! ¡Vas por un excelente camino!'
    ];

    ob_start();
    ?>
    <div class="side-mascot-companion" id="sideCompanion_<?= htmlspecialchars($charSlug) ?>">
      <!-- Avatar Peeking / Asomado a un lado de la tarjeta -->
      <div class="side-mascot-figure" onclick="advanceMascotSideDialogue('<?= htmlspecialchars($charSlug) ?>')">
        <div class="side-mascot-glow" style="background: radial-gradient(circle, <?= htmlspecialchars($char['color_tema']) ?> 0%, rgba(255,255,255,0) 70%);"></div>
        <img src="<?= htmlspecialchars($imageWebPath) ?>" 
             alt="<?= htmlspecialchars($char['nombre']) ?>" 
             class="side-mascot-img"
             id="sideMascotImg_<?= htmlspecialchars($charSlug) ?>">
        <div class="side-mascot-tap-badge">
          <span>💬 ¡Tócame!</span>
        </div>
      </div>

      <!-- Cuadro de Diálogo Lateral con Flecha apuntando al Personaje -->
      <div class="side-mascot-speech-box" style="border-color: <?= htmlspecialchars($char['color_tema']) ?>;">
        <div class="side-speech-top">
          <div style="display: flex; align-items: center; gap: 0.35rem;">
            <strong style="color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($char['nombre']) ?></strong>
            <span class="mascot-subject-pill" style="font-size: 0.68rem; padding: 0.1rem 0.45rem; color: <?= htmlspecialchars($char['color_tema']) ?>; border-color: <?= htmlspecialchars($char['color_tema']) ?>;">Tutor</span>
          </div>
          <button type="button" class="mascot-sound-btn" onclick="SoundFx.coin()" title="Celebrar">
            <span>⭐</span>
          </button>
        </div>

        <div class="side-speech-message" id="sideSpeechMessage_<?= htmlspecialchars($charSlug) ?>">
          <?= htmlspecialchars($tips[0]) ?>
        </div>

        <div class="side-speech-action">
          <button type="button" 
                  class="btn-mascot-tip" 
                  onclick="advanceMascotSideDialogue('<?= htmlspecialchars($charSlug) ?>')">
            <span>💡 Siguiente Consejo ➔</span>
          </button>
        </div>
      </div>
    </div>

    <script>
    (function() {
      const tips_<?= htmlspecialchars($charSlug) ?> = <?= json_encode($tips, JSON_UNESCAPED_UNICODE) ?>;
      let currentTipIndex_<?= htmlspecialchars($charSlug) ?> = 0;

      window.advanceMascotSideDialogue = function(slug) {
        if (slug !== '<?= htmlspecialchars($charSlug) ?>') return;
        currentTipIndex_<?= htmlspecialchars($charSlug) ?> = (currentTipIndex_<?= htmlspecialchars($charSlug) ?> + 1) % tips_<?= htmlspecialchars($charSlug) ?>.length;
        
        const msgEl = document.getElementById('sideSpeechMessage_' + slug);
        const imgEl = document.getElementById('sideMascotImg_' + slug);
        
        if (msgEl) {
          msgEl.style.opacity = '0';
          msgEl.style.transform = 'translateY(4px)';
          setTimeout(() => {
            msgEl.textContent = tips_<?= htmlspecialchars($charSlug) ?>[currentTipIndex_<?= htmlspecialchars($charSlug) ?>];
            msgEl.style.opacity = '1';
            msgEl.style.transform = 'translateY(0)';
          }, 150);
        }

        if (imgEl) {
          imgEl.classList.remove('bounce-once');
          void imgEl.offsetWidth; // trigger reflow
          imgEl.classList.add('bounce-once');
        }

        SoundFx.click();
      };
    })();
    </script>
    <?php
    return ob_get_clean();
}

