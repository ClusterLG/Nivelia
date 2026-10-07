# 🚀 Nivelia — Plataforma de Refuerzo y Nivelación Académica 🇻🇪

Plataforma educativa y de nivelación continua desarrollada para **Alexander (6to Grado)** y **Dimas (5to Grado)**.

---

## 🔐 Acceso a la Plataforma (Comenzar Sesión)

Para acceder desde la computadora o desde los teléfonos celulares conectados al Wi-Fi de la casa:

* **Desde la Computadora:**
  ```
  http://localhost/Estudio%20en%20Casa/
  ```
* **Desde los Teléfonos Celulares (Wi-Fi):**
  ```
  http://192.168.1.109/Estudio%20en%20Casa/
  ```

Todos los usuarios ingresan a la pantalla de **Comenzar Sesión** escribiendo su correo electrónico y su contraseña individual:

| Usuario | Rol / Grado | Correo Electrónico | Contraseña | Espacio al que Ingresa |
| :--- | :--- | :--- | :--- | :--- |
| **Papá** | 👨‍🏫 Administrador | `papa@estudio.local` | `papa123` | Panel de Control y Planificador |
| **Alexander** | 🚀 6to Grado | `alexander@estudio.local` | `alex123` | Misiones Diarias de 6to Grado |
| **Dimas** | ⚡ 5to Grado | `moralesponcedimasadrian@gmail.com` | `adrian3108` | Misiones Diarias de 5to Grado |

*(Papá puede cambiar las contraseñas de cualquier cuenta en cualquier momento desde el botón **🔑 Contraseñas** en su panel).*

---

## 📋 Funcionalidades Principales

### 1. Panel de Administración de Papá
* **Planificador Semanal (`/admin/planner.php`)**:
  * Tabla con los objetivos didácticos y las misiones diarias de Lunes a Viernes para 5to y 6to grado.
  * Activa o pausa actividades con un solo clic.
  * Formulario para crear nuevas lecciones o ejercicios cuando lo desees.
* **Módulo de Calificaciones (`/admin/grade.php`)**:
  * Revisa las respuestas enviadas por los niños.
  * Asigna estrellas (⭐ 1 a 5) y puntos extra de motivación (XP).
  * Envía mensajes pedagógicos personalizados de aliento o corrección.
* **Métricas e Historial Académico (`/admin/metrics.php`)**:
  * Resumen global de rendimiento con barras de progreso por materia.
  * Explorador visual de los archivos guardados en la carpeta `respuestas/`.
  * Visor en pantalla para leer los reportes y datos JSON generados.
* **Gestión de Cuentas y Contraseñas (`/admin/passwords.php`)**:
  * Permite cambiar las contraseñas de Alex, Dimas y Papá.

---

## 📁 Archivo Físico en Disco (`respuestas/`)

Cada vez que un estudiante responde una misión o Papá califica una actividad, el sistema crea y actualiza automáticamente los archivos en el proyecto:

```text
respuestas/
├── Alexander/
│   ├── YYYY-MM-DD_dia_materia_titulo.json   (Datos estructurados)
│   ├── YYYY-MM-DD_dia_materia_titulo.md     (Reporte detallado legible)
│   └── RESUMEN_HISTORIAL.md                 (Historial cronológico)
└── Dimas/
    ├── YYYY-MM-DD_dia_materia_titulo.json
    ├── YYYY-MM-DD_dia_materia_titulo.md
    └── RESUMEN_HISTORIAL.md
```

### 2. Entorno para Alexander y Dimas
* **Misiones del Día**: Tarjetas interactivas organizadas por día de la semana.
* **Lectura y Resolución**: Explicaciones paso a paso con preguntas interactivas.
* **Gamificación**: Puntos de Experiencia (XP), medallas desbloqueables, confeti animado y sonidos de éxito.

### 3. Módulo Especial: "Defensores de La Guaira"
* Sismicidad y la Falla de San Sebastián.
* Crecidas de quebradas, cuencas del Waraira Repano y señales de alerta en la montaña.
* Aludes torrenciales, saturación de suelos y presas de retención de sedimentos.
* Sismos marinos, alerta de tsunamis y evacuación hacia cotas altas.
* Misión de la **Mochila de las 72 Horas** para la familia.
