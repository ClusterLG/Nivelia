/**
 * Nivelia - Interactive Logic, Audio & Confetti
 */

// Web Audio API Synthesized Sound Effects
const SoundFx = {
  ctx: null,
  init() {
    if (!this.ctx) {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (AudioCtx) this.ctx = new AudioCtx();
    }
  },
  playTone(freq, type, duration, delay = 0) {
    try {
      this.init();
      if (!this.ctx) return;
      setTimeout(() => {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.15, this.ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + duration);
        osc.connect(gain);
        gain.connect(this.ctx.destination);
        osc.start();
        osc.stop(this.ctx.currentTime + duration);
      }, delay * 1000);
    } catch(e) {}
  },
  coin() {
    this.playTone(987.77, 'sine', 0.1, 0);
    this.playTone(1318.51, 'sine', 0.25, 0.08);
  },
  success() {
    this.playTone(523.25, 'triangle', 0.15, 0);     // C5
    this.playTone(659.25, 'triangle', 0.15, 0.1);   // E5
    this.playTone(783.99, 'triangle', 0.15, 0.2);   // G5
    this.playTone(1046.50, 'triangle', 0.4, 0.3);   // C6
  },
  click() {
    this.playTone(400, 'sine', 0.05, 0);
  }
};

// Canvas Confetti Celebration
function triggerConfetti() {
  const canvas = document.createElement('canvas');
  canvas.id = 'confetti-canvas';
  canvas.style.position = 'fixed';
  canvas.style.top = '0';
  canvas.style.left = '0';
  canvas.style.width = '100vw';
  canvas.style.height = '100vh';
  canvas.style.pointerEvents = 'none';
  canvas.style.zIndex = '9999';
  document.body.appendChild(canvas);

  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const particles = [];
  const colors = ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#ec4899'];

  for (let i = 0; i < 90; i++) {
    particles.push({
      x: window.innerWidth / 2,
      y: window.innerHeight / 2,
      vx: (Math.random() - 0.5) * 14,
      vy: (Math.random() - 0.5) * 14 - 3,
      size: Math.random() * 8 + 4,
      color: colors[Math.floor(Math.random() * colors.length)],
      rotation: Math.random() * 360,
      vRot: (Math.random() - 0.5) * 10,
      opacity: 1
    });
  }

  let startTime = Date.now();
  function render() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    let alive = false;
    particles.forEach(p => {
      p.x += p.vx;
      p.y += p.vy;
      p.vy += 0.35; // gravity
      p.rotation += p.vRot;
      p.opacity -= 0.012;

      if (p.opacity > 0) {
        alive = true;
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate((p.rotation * Math.PI) / 180);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = Math.max(0, p.opacity);
        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
        ctx.restore();
      }
    });

    if (alive && Date.now() - startTime < 3000) {
      requestAnimationFrame(render);
    } else {
      canvas.remove();
    }
  }
  render();
}

// Interactive Radio Option Selection
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.option-label input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      SoundFx.click();
      const parentList = radio.closest('.options-list');
      if (parentList) {
        parentList.querySelectorAll('.option-label').forEach(label => label.classList.remove('selected'));
      }
      radio.closest('.option-label').classList.add('selected');
    });
  });

  // User interaction listener to allow Web Audio API
  document.addEventListener('click', () => {
    SoundFx.init();
  }, { once: true });

  // Initialize Habitat Scroll Parallax if container is present
  initHabitatScrollParallax();
});

// Habitat Scroll Parallax & Frame Interpolation (0% top frame -> 100% bottom frame)
function initHabitatScrollParallax() {
  const container = document.getElementById('habitatParallaxContainer');
  if (!container) return;

  function updateScrollProgress() {
    const doc = document.documentElement;
    const body = document.body;
    const scrollTop = window.pageYOffset || doc.scrollTop || body.scrollTop || 0;
    const scrollHeight = Math.max(
      doc.scrollHeight, body.scrollHeight,
      doc.offsetHeight, body.offsetHeight,
      doc.clientHeight
    );
    const windowHeight = window.innerHeight || doc.clientHeight;
    const maxScroll = scrollHeight - windowHeight;

    let progress = 0;
    if (maxScroll > 0) {
      progress = Math.min(Math.max(scrollTop / maxScroll, 0), 1);
    }

    // Set CSS custom property with smooth decimal precision
    container.style.setProperty('--scroll-progress', progress.toFixed(4));

    // Update dynamic label or frame indicator if present
    const indicatorText = container.querySelector('.indicator-progress-text');
    if (indicatorText) {
      const pct = Math.round(progress * 100);
      if (pct <= 15) {
        indicatorText.textContent = 'Fotograma 1: Cima y Cielo';
      } else if (pct >= 85) {
        indicatorText.textContent = 'Fotograma Final: Fondo y Suelo';
      } else {
        indicatorText.textContent = `Descendiendo: ${pct}%`;
      }
    }
  }

  window.addEventListener('scroll', updateScrollProgress, { passive: true });
  window.addEventListener('resize', updateScrollProgress);
  
  // Initial calculations
  updateScrollProgress();
  setTimeout(updateScrollProgress, 100);
  setTimeout(updateScrollProgress, 400);
  window._updateHabitatParallax = updateScrollProgress;
}

// Modal Helpers
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.remove('active');
}

// Background session heartbeat (updates duration and active state every 60s)
if (typeof window !== 'undefined') {
  setInterval(() => {
    const base = window.APP_BASE_URL || '/Estudio%20en%20Casa/';
    fetch(base + 'heartbeat.php', { method: 'POST', credentials: 'same-origin' })
      .catch(() => {});
  }, 60000);
}

