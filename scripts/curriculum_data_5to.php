<?php
/**
 * Banco Curricular Completo - 5to Grado de Primaria (Dimas)
 * 3 Lapsos Consecutivos x 10 Semanas cada uno = 30 Semanas (150 Misiones Escolares)
 * 
 * Distribución de materias:
 * Lunes: Lectura y Lengua (ID 1)
 * Martes: Matemáticas (ID 2)
 * Miércoles: Biología y Ecosistemas / Ciencias (ID 13)
 * Jueves: Defensores de La Guaira / Geografía (ID 6)
 * Viernes: Formación Ciudadana y Valores / Historia (ID 14 o 4)
 */

function getCurriculum5to() {
    $weeks = [];

    // =========================================================================
    // LAPSO 1: FUNDAMENTOS Y DIAGNÓSTICO (Semanas 1 a 10)
    // =========================================================================

    // SEMANA 1
    $weeks[1] = [
        'lapso' => 1,
        'lapso_week' => 1,
        'theme' => 'Bienvenida, Valor Posicional y Convivencia',
        'days' => [
            'lunes' => [
                'subject_id' => 1,
                'objective' => 'Comprender la estructura de los textos narrativos e identificar la idea principal y los personajes.',
                'title' => 'El Viaje de la Semilla: Idea Principal y Secuencia Narrativa',
                'summary' => 'Aprende a identificar la idea principal en un cuento y distinguir el inicio, nudo y desenlace.',
                'reading' => "Los textos narrativos cuentan historias reales o imaginarias que ocurren en un lugar y tiempo determinados. Toda narración bien construida tiene tres partes fundamentales:\n\n1. **Inicio:** Se presentan los personajes, el lugar y la situación inicial.\n2. **Nudo o conflicto:** Ocurre un problema o acontecimiento que cambia la tranquilidad de los personajes.\n3. **Desenlace:** Se resuelve el conflicto y concluye la historia.\n\nPara hallar la **idea principal**, pregúntate siempre: *¿De qué o de quién trata principalmente el texto y qué es lo más importante que se dice sobre ello?*",
                'exercise' => "Escribe en tu cuaderno un cuento corto de 6 líneas sobre una mascota que encuentra un mapa secreto. Señala con colores distintos el inicio, el nudo y el desenlace.",
                'xp' => 50,
                'questions' => [
                    [
                        'text' => '¿Cuál es la parte de la narración donde surge el problema principal que los personajes deben resolver?',
                        'type' => 'multiple_choice',
                        'options' => ['El nudo o conflicto', 'El desenlace', 'El título', 'El prólogo'],
                        'correct' => 'El nudo o conflicto',
                        'explanation' => 'En el nudo se presenta el conflicto central que pone a prueba a los personajes.',
                        'points' => 10
                    ],
                    [
                        'text' => 'La idea principal de un texto es siempre la palabra más larga del primer párrafo.',
                        'type' => 'true_false',
                        'options' => ['Verdadero', 'Falso'],
                        'correct' => 'Falso',
                        'explanation' => 'La idea principal es la idea central que resume lo más importante que el autor comunica.',
                        'points' => 10
                    ]
                ]
            ],
            'martes' => [
                'subject_id' => 2,
                'objective' => 'Comprender el sistema de numeración decimal y el valor de posición hasta los millones.',
                'title' => 'El Sistema de Numeración Decimal: Unidades, Decenas y Millones',
                'summary' => 'Descubre cómo cada dígito cambia de valor según su posición en la tabla de valor posicional.',
                'reading' => "Nuestro sistema de numeración es **posicional y de base 10**. Esto significa que diez unidades de un orden forman una unidad del orden inmediatamente superior:\n\n- 10 Unidades (U) = 1 Decena (D)\n- 10 Decenas (D) = 1 Centena (C)\n- 10 Centenas (C) = 1 Unidad de Mil (UM)\n- 1.000 Unidades de Mil = 1 Unidad de Millón (UMi)\n\nPor ejemplo, en el número **4.528.319**:\nEl dígito 4 está en las unidades de millón y vale 4.000.000.\nEl dígito 5 está en las centenas de mil y vale 500.000.",
                'exercise' => "Descompón aditivamente el número 3.842.105 según el valor posicional de cada una de sus cifras en tu cuaderno.",
                'xp' => 50,
                'questions' => [
                    [
                        'text' => 'En el número 7.625.410, ¿cuál es el valor de posición del dígito 6?',
                        'type' => 'multiple_choice',
                        'options' => ['600.000', '60.000', '6.000.000', '6.000'],
                        'correct' => '600.000',
                        'explanation' => 'El 6 ocupa las centenas de mil, por lo que equivale a 6 x 100.000 = 600.000 unidades.',
                        'points' => 10
                    ],
                    [
                        'text' => 'Diez centenas son equivalentes exactamente a 1.000 unidades.',
                        'type' => 'true_false',
                        'options' => ['Verdadero', 'Falso'],
                        'correct' => 'Verdadero',
                        'explanation' => '10 x 100 = 1.000 unidades, lo cual forma 1 Unidad de Mil.',
                        'points' => 10
                    ]
                ]
            ],
            'miercoles' => [
                'subject_id' => 13,
                'objective' => 'Identificar la célula como unidad básica de todo ser vivo y diferenciar animales y vegetales.',
                'title' => 'La Célula: El Ladrillo Fundamental de los Seres Vivos',
                'summary' => 'Explora cómo están formados todos los organismos vivos y la diferencia entre células animales y vegetales.',
                'reading' => "Todos los seres vivos en la Tierra, desde las bacterias microscópicas hasta los jaguares y samanes, están formados por **células**. Las tres partes esenciales de toda célula eucariota son:\n\n1. **Membrana plasmática:** Envoltura que protege a la célula y controla qué sustancias entran y salen.\n2. **Citoplasma:** Líquido gelatinoso donde flotan los organelos encargados de las funciones celulares.\n3. **Núcleo:** El centro de mando que contiene el ADN (material genético).\n\n*Diferencia clave:* Las células vegetales tienen **pared celular rígida** y **cloroplastos** (para hacer fotosíntesis con la luz solar), organelos que no tienen las células animales.",
                'exercise' => "Dibuja en tu cuaderno una célula vegetal y rotula sus tres partes principales más la pared celular.",
                'xp' => 50,
                'questions' => [
                    [
                        'text' => '¿Qué organelo celular exclusivo de las plantas les permite realizar la fotosíntesis?',
                        'type' => 'multiple_choice',
                        'options' => ['Cloroplasto', 'Mitocondria', 'Ribosoma', 'Lisosoma'],
                        'correct' => 'Cloroplasto',
                        'explanation' => 'Los cloroplastos contienen clorofila y captan la luz del sol para producir glucosa.',
                        'points' => 10
                    ],
                    [
                        'text' => 'El núcleo celular contiene las instrucciones genéticas (ADN) de la célula.',
                        'type' => 'true_false',
                        'options' => ['Verdadero', 'Falso'],
                        'correct' => 'Verdadero',
                        'explanation' => 'El núcleo funciona como el centro de control genético de la célula eucariota.',
                        'points' => 10
                    ]
                ]
            ],
            'jueves' => [
                'subject_id' => 6,
                'objective' => 'Reconocer el relieve y la geografía del Litoral Central y las medidas básicas de prevención.',
                'title' => 'Nuestra Geografía: La Cordillera de la Costa y La Guaira',
                'summary' => 'Conoce cómo las montañas se unen con el mar Caribe en La Guaira y por qué debemos cuidar las cuencas.',
                'reading' => "El estado La Guaira posee una geografía muy especial: es una estrecha franja costera flanqueada por la imponente **Cordillera de la Costa** (Parque Nacional Waraira Repano) y el mar Caribe.\n\nDebido a que las montañas tienen pendientes muy pronunciadas, las quebradas y ríos bajan rápidamente hacia el mar. Cuando caen lluvias torrenciales continuas, el agua arrastra sedimentos y rocas.\n\nPor esta razón, la **gestión de riesgos** es una prioridad para todos los guaireños:\n- Mantener limpias las quebradas sin arrojar basura.\n- Reforestar las laderas montañosas para que las raíces afiancen la tierra.\n- Conocer las rutas de evacuación hacia zonas altas y seguras.",
                'exercise' => "Conversa con tu familia e identifiquen juntos cuál es la zona alta más segura cercana a su vivienda en caso de lluvias fuertes.",
                'xp' => 50,
                'questions' => [
                    [
                        'text' => '¿Cuál parque nacional separa el valle de Caracas del estado La Guaira?',
                        'type' => 'multiple_choice',
                        'options' => ['Waraira Repano (El Ávila)', 'Canaima', 'Mochima', 'Morrocoy'],
                        'correct' => 'Waraira Repano (El Ávila)',
                        'explanation' => 'El Waraira Repano forma parte de la Cordillera de la Costa y se ubica entre Caracas y La Guaira.',
                        'points' => 10
                    ],
                    [
                        'text' => 'Arrojar basura a los cauces de los ríos y quebradas ayuda a que el agua fluya mejor.',
                        'type' => 'true_false',
                        'options' => ['Verdadero', 'Falso'],
                        'correct' => 'Falso',
                        'explanation' => 'La basura represa el agua y provoca desbordamientos peligrosos de ríos y quebradas.',
                        'points' => 10
                    ]
                ]
            ],
            'viernes' => [
                'subject_id' => 14,
                'objective' => 'Valorar la convivencia escolar, el respeto mutuo y las normas como base de la paz.',
                'title' => 'El Arte de Convivir: Acuerdos de Paz y Respeto Mutuo',
                'summary' => 'Descubre por qué las normas no son castigos sino acuerdos que protegen nuestros derechos.',
                'reading' => "La convivencia es la capacidad de vivir juntos en armonía, respetando las ideas, gustos y diferencias de los demás. En la casa y en la escuela, los **acuerdos de convivencia** son indispensables:\n\n- **Escucha activa:** Escuchar cuando otra persona habla sin interrumpirla.\n- **Palabras mágicas:** Por favor, gracias, con permiso y disculpa.\n- **Empatía:** Ponerse en los zapatos del compañero antes de emitir un juicio.\n- **Solución pacífica:** Dialogar con calma cuando surge un desacuerdo en lugar de pelear o gritar.\n\nCuando practicamos estos valores, nuestro hogar y nuestra aula se convierten en un refugio de aprendizaje y amistad.",
                'exercise' => "Escribe en tu cuaderno tres acuerdos de convivencia que te comprometes a cumplir esta semana en tu hogar con tus padres y hermanos.",
                'xp' => 50,
                'questions' => [
                    [
                        'text' => '¿Cuál es la mejor herramienta para resolver un desacuerdo entre compañeros?',
                        'type' => 'multiple_choice',
                        'options' => ['El diálogo sereno y respetuoso', 'Ignorar el problema y molestarse', 'Gritar más fuerte', 'Acusar sin escuchar'],
                        'correct' => 'El diálogo sereno y respetuoso',
                        'explanation' => 'El diálogo y la escucha activa permiten entender los puntos de vista y hallar soluciones justas.',
                        'points' => 10
                    ],
                    [
                        'text' => 'La empatía significa alegrarse cuando a otro compañero le va mal.',
                        'type' => 'true_false',
                        'options' => ['Verdadero', 'Falso'],
                        'correct' => 'Falso',
                        'explanation' => 'La empatía es comprender los sentimientos de los demás y actuar con solidaridad y respeto.',
                        'points' => 10
                    ]
                ]
            ]
        ]
    ];

    // GENERAR CONTENIDOS EXHAUSTIVOS SEMANAS 2 A 30 PARA 5TO GRADO
    $temarios5to = [
        // LAPSO 1 (Semanas 2 a 10)
        2 => [
            'lapso' => 1, 'theme' => 'Decimales, El Párrafo y La Fotosíntesis',
            'd' => [
                'lunes' => [1, 'El Párrafo: Oración Principal y Conectores de Causa y Efecto', 'Aprende a estructurar párrafos usando conectores como porque, por tanto y debido a.', 'Un párrafo es un conjunto de oraciones que desarrollan una misma idea...', 'Redacta un párrafo de 5 líneas sobre tu comida favorita usando al menos dos conectores.', '¿Qué conector indica causa?', 'porque', ['porque', 'además', 'finalmente', 'primero']],
                'martes' => [2, 'Adición y Sustracción con Números Decimales', 'Domina la alineación correcta de la coma decimal para sumar y restar con precisión.', 'Para sumar o restar números decimales, alineamos siempre las comas en una misma columna...', 'Resuelve: 456,78 + 89,45 y 500 - 123,45 en tu cuaderno.', 'Al sumar 12,5 + 3,25 el resultado correcto es:', '15,75', ['15,75', '4,50', '15,30', '15,05']],
                'miercoles' => [13, 'La Fotosíntesis: La Gran Fábrica Verde de Oxígeno', 'Descubre cómo las plantas convierten la luz solar, agua y CO2 en glucosa y oxígeno.', 'Las plantas son organismos autótrofos capaces de producir su propio alimento gracias a la fotosíntesis...', 'Explica por qué los bosques son llamados los pulmones del planeta.', '¿Qué gas absorben las plantas durante la fotosíntesis?', 'Dióxido de carbono', ['Dióxido de carbono', 'Oxígeno', 'Nitrógeno', 'Helio']],
                'jueves' => [6, 'Estados y Regiones de Venezuela: Los Andes y el Litoral', 'Conoce las diferentes regiones geográficas y climas de nuestro país.', 'Venezuela está dividida en grandes regiones naturales: Los Andes, Los Llanos, Guayana y Costa-Montaña...', 'Menciona tres estados que pertenecen a la región Costa-Montaña.', '¿En qué región natural se encuentra el estado La Guaira?', 'Costa-Montaña', ['Costa-Montaña', 'Los Llanos', 'Guayana', 'La Selva Amazónica']],
                'viernes' => [14, 'Derechos y Deberes de los Niños: LOPNNA y Protección', 'Aprende que todo derecho implica un deber correspondiente para crecer en equilibrio.', 'La LOPNNA protege los derechos de niños y adolescentes en Venezuela...', 'Escribe dos deberes que tienes en tu hogar para apoyar a tu familia.', 'Todo niño tiene derecho a la educación y el deber de estudiar.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        3 => [
            'lapso' => 1, 'theme' => 'Multiplicación, Vocabulario y Ecosistemas',
            'd' => [
                'lunes' => [1, 'Sinónimos, Antónimos y Enriquecimiento de Vocabulario', 'Descubre cómo ampliar tu vocabulario para expresarte con mayor elegancia y exactitud.', 'Los sinónimos son palabras que tienen un significado similar, mientras que los antónimos significan lo opuesto...', 'Escribe tres parejas de sinónimos y tres de antónimos en tu cuaderno.', '¿Cuál es un antónimo de la palabra valiente?', 'Cobarde', ['Cobarde', 'Fuerte', 'Audaz', 'Inteligente']],
                'martes' => [2, 'Multiplicación por Dos y Tres Cifras', 'Multiplica con soltura y comprende el algoritmo y la propiedad conmutativa y asociativa.', 'Para multiplicar números de varias cifras, multiplicamos primero las unidades, luego las decenas dejando el espacio...', 'Calcula: 245 x 36 en tu cuaderno.', 'El resultado de multiplicar 150 x 20 es:', '3.000', ['3.000', '300', '30.000', '1.500']],
                'miercoles' => [13, 'Ecosistemas Terrestres y Acuáticos de Venezuela', 'Explora la gran diversidad de flora y fauna en sabanas, selvas y arrecifes de coral.', 'Un ecosistema es una comunidad de seres vivos que interactúan con su medio físico...', 'Describe brevemente las características de un ecosistema de manglar en Venezuela.', '¿Qué animal es típico de los llanos venezolanos?', 'El Chigüire', ['El Chigüire', 'El Oso Polar', 'El Canguro', 'El Pingüino']],
                'jueves' => [6, 'El Relieve Venezolano: Picos Nevados y Grandes Llanuras', 'Aprende a diferenciar valles, mesetas (tepuyes) y llanuras en el mapa físico.', 'Venezuela posee tepuyes milenarios en Guayana y picos nevados en Mérida...', 'Dibuja en tu cuaderno el perfil de un tepuy y explica qué lo hace único.', 'El pico más alto de Venezuela es el:', 'Pico Bolívar', ['Pico Bolívar', 'Pico Naiguatá', 'Pico Humboldt', 'Pico Oriental']],
                'viernes' => [14, 'Hábitos de Estudio y Gestión del Tiempo Diario', 'Organiza tu rutina de tareas para aprender sin estrés y disfrutar de tu tiempo libre.', 'El éxito escolar no depende de estudiar a última hora, sino de la constancia...', 'Elabora un horario semanal de 30 minutos diarios dedicado al estudio de misiones.', 'Estudiar 30 minutos todos los días es más efectivo que estudiar 5 horas seguidas la noche anterior.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        4 => [
            'lapso' => 1, 'theme' => 'División, Tipos de Oraciones y Cadenas Alimentarias',
            'd' => [
                'lunes' => [1, 'Tipos de Oraciones: Enunciativas, Interrogativas y Exclamativas', 'Reconoce la intención del emisor a través de la entonación y signos ortográficos.', 'Las oraciones enunciativas afirman o niegan algo, las interrogativas preguntan...', 'Escribe una oración de cada tipo sobre el cielo estrellado.', '¿Qué signos se usan para expresar sorpresa o entusiasmo?', 'Signos de exclamación ¡ !', ['Signos de exclamación ¡ !', 'Signos de interrogación ¿ ?', 'Comillas « »', 'Paréntesis ( )']],
                'martes' => [2, 'División con Divisores de Dos Cifras', 'Comprende el cociente, el residuo y el algoritmo para repartir en partes iguales.', 'Dividir es repartir una cantidad en partes iguales...', 'Resuelve: 864 ÷ 24 y comprueba que Dividendo = Divisor x Cociente + Residuo.', 'Al dividir 100 entre 4, el cociente exacto es:', '25', ['25', '20', '30', '40']],
                'miercoles' => [13, 'Cadenas y Redes Alimentarias: Productores y Consumidores', 'Aprende cómo fluye la energía del sol a través de plantas, herbívoros y carnívoros.', 'En toda cadena trófica, las plantas son productores primarios...', 'Dibuja una cadena alimentaria con: pasto, venado, jaguar y bacterias descomponedoras.', '¿Qué organismo ocupa el nivel de productor en una cadena trófica?', 'La planta verde', ['La planta verde', 'El león', 'El águila', 'El hongo']],
                'jueves' => [6, 'Las Grandes Cuencas Hidrográficas de Venezuela: El Río Orinoco', 'Descubre el río padre de Venezuela y su importancia fluvial y ecológica.', 'El río Orinoco es uno de los más caudalosos del mundo...', 'Investiga qué estados recorre el río Orinoco hasta su desembocadura.', 'El río Orinoco desemboca en el océano:', 'Atlántico', ['Atlántico', 'Pacífico', 'Índico', 'Ártico']],
                'viernes' => [14, 'La Honestidad y la Verdad en la Familia y la Escuela', 'Comprende por qué ser sincero genera confianza y nos hace personas dignas.', 'La honestidad es el valor de hablar y actuar siempre con la verdad...', 'Escribe una reflexión de 3 líneas sobre por qué la verdad siempre nos libera.', 'Decir la verdad aunque hayamos cometido un error demuestra valentía y madurez.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        5 => [
            'lapso' => 1, 'theme' => 'Múltiplos, Divisores, Sustantivos y Prevención',
            'd' => [
                'lunes' => [1, 'Sustantivos y Adjetivos: Calificativos y Concordancia de Género y Número', 'Aprende a enriquecer tus oraciones uniendo sustantivos con adjetivos que concuerden.', 'El sustantivo nombra personas, animales o cosas, y el adjetivo describe cómo son...', 'Escribe 4 oraciones donde el adjetivo concuerde en género y número con el sustantivo.', 'En la frase "Las hermosas guacamayas vuelan alto", ¿cuál es el adjetivo calificativo?', 'hermosas', ['hermosas', 'guacamayas', 'vuelan', 'alto']],
                'martes' => [2, 'Múltiplos y Divisores: Criterios de Divisibilidad (2, 3, 5 y 10)', 'Descubre cómo saber si un número es divisible entre otro sin hacer la división larga.', 'Un número es divisible entre 2 si termina en cifra par, entre 5 si termina en 0 o 5...', 'Identifica si 135 es divisible entre 3 y entre 5 aplicando los criterios.', '¿Cuál de los siguientes números es divisible exactamente entre 5 y entre 10?', '70', ['70', '75', '83', '92']],
                'miercoles' => [13, 'Animales Vertebrados e Invertebrados: Clasificación Fascinante', 'Distingue animales con columna vertebral (mamíferos, aves, reptiles) de invertebrados.', 'Los vertebrados poseen esqueleto interno y columna ósea...', 'Haz una lista de 3 vertebrados y 3 invertebrados típicos de Venezuela.', '¿Cuál de los siguientes animales es un invertebrado?', 'El pulpo', ['El pulpo', 'El delfín', 'La guacamaya', 'La iguana']],
                'jueves' => [6, 'Plan Familiar de Emergencia: Mochila de las 72 Horas', 'Aprende a preparar los elementos esenciales que salvan vidas ante contingencias.', 'Una mochila de emergencia contiene agua potable, alimentos no perecederos, botiquín...', 'Haz con tus padres una lista de los objetos indispensables para su mochila familiar.', '¿Qué elemento NO debe faltar en un botiquín de primeros auxilios?', 'Alcohol y gasas estériles', ['Alcohol y gasas estériles', 'Dulces y refrescos', 'Juguetes pesados', 'Joyas']],
                'viernes' => [14, 'El Valor de la Paciencia y el Autocontrol Emocional', 'Aprende a respirar hondo y pensar antes de reaccionar cuando algo te frustra.', 'La paciencia nos enseña a esperar con calma y serenidad...', 'Escribe una situación donde tuviste que ser paciente y cómo te ayudó.', 'El autocontrol nos permite responder con inteligencia en lugar de reaccionar con ira.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        6 => [
            'lapso' => 1, 'theme' => 'Números Primos, Signos de Puntuación y Parques Nacionales',
            'd' => [
                'lunes' => [1, 'Signos de Puntuación: El Punto, La Coma y El Punto y Coma', 'Aprende a dar ritmo y claridad a tus textos para que transmitan el mensaje exacto.', 'El punto indica el fin de una oración, la coma separa elementos en una lista...', 'Coloca comas en una lista de compras de 5 frutas venezolanas.', 'El punto y seguido se utiliza para separar oraciones dentro de un mismo:', 'Párrafo', ['Párrafo', 'Libro', 'Capítulo', 'Título']],
                'martes' => [2, 'Números Primos y Compuestos: La Criba de Eratóstenes', 'Descubre cuáles números solo pueden dividirse entre 1 y sí mismos.', 'Un número primo solo tiene dos divisores: el 1 y él mismo. Los compuestos tienen más...', 'Escribe los primeros 5 números primos en tu cuaderno.', '¿Cuál de estos números es un número primo?', '13', ['13', '12', '15', '21']],
                'miercoles' => [13, 'Plantas Medicinales y Autóctonas de Venezuela', 'Conoce las propiedades de la sábila, el malojillo, la manzanilla y el llantén.', 'Nuestros abuelos e indígenas descubrieron propiedades curativas en plantas...', 'Pregunta a un familiar cómo preparan una infusión de malojillo y anótala.', 'La sábila (aloe vera) es ampliamente reconocida por sus propiedades para la piel.', 'Verdadero', ['Verdadero', 'Falso']],
                'jueves' => [6, 'Parques Nacionales: Santuarios de la Naturaleza Venezolana', 'Conoce Canaima, Morrocoy, Mochima y Henri Pittier y la protección de la biosfera.', 'Venezuela cuenta con más de 40 parques nacionales que protegen ecosistemas vitales...', 'Investiga qué parque nacional tiene la caída de agua más alta del mundo.', 'El Salto Ángel se encuentra ubicado en el Parque Nacional:', 'Canaima', ['Canaima', 'Morrocoy', 'El Ávila', 'Sierra Nevada']],
                'viernes' => [14, 'Solidaridad y Apoyo a los Adultos Mayores', 'Aprende a honrar a los abuelos y apoyar a quienes necesitan una mano amiga.', 'La solidaridad es tender la mano con cariño sin esperar nada a cambio...', 'Describe una acción solidaria que puedes realizar esta semana en tu comunidad.', 'Escuchar las historias de los abuelos es una forma hermosa de respeto y cariño.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        7 => [
            'lapso' => 1, 'theme' => 'Fracciones, Reglas de Acentuación y El Ciclo del Agua',
            'd' => [
                'lunes' => [1, 'Reglas de Acentuación: Palabras Agudas, Graves y Esdrújulas', 'Aprende cuándo llevan tilde según la sílaba tónica y la letra final.', 'Las agudas llevan tilde si terminan en n, s o vocal; las esdrújulas siempre llevan tilde...', 'Clasifica en tu cuaderno: árbol, canción, teléfono, compás y lámpara.', 'La palabra "canción" lleva tilde porque es una palabra aguda que termina en:', 'n', ['n', 's', 'vocal', 'consonante cualquiera']],
                'martes' => [2, 'Introducción a las Fracciones: Numerador, Denominador y Representación Gráfica', 'Entiende qué significa partir una pizza o chocolate en partes iguales.', 'El denominador indica en cuántas partes iguales se divide la unidad, el numerador cuántas tomamos...', 'Dibuja un círculo dividido en 8 partes y colorea 3/8.', 'En la fracción 3/5, ¿cuál número es el numerador?', '3', ['3', '5', '8', '2']],
                'miercoles' => [13, 'El Ciclo del Agua: Evaporación, Condensación y Precipitación', 'Descubre el viaje infinito del agua desde los mares hasta las nubes y las montañas.', 'El sol calienta los océanos, el agua se evapora, forma nubes y cae como lluvia...', 'Dibuja el ciclo del agua rotulando sus tres fases principales.', '¿Cómo se llama el proceso en el que el vapor de agua forma nubes en la atmósfera?', 'Condensación', ['Condensación', 'Evaporación', 'Precipitación', 'Filtración']],
                'jueves' => [6, 'Población y Culturas Regionales de Venezuela', 'Valora las tradiciones, la música llanera, los tambores de las costas y los gaiteros.', 'Nuestra identidad venezolana es el fruto del mestizaje indígena, africano y europeo...', 'Escribe dos instrumentos musicales típicos de las distintas regiones de Venezuela.', 'El cuatro venezolano es el instrumento emblemático de nuestra música tradicional.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Inclusión y Respeto a las Diferencias de Cada Persona', 'Aprende que la diversidad de talentos y personalidades hace rico a un grupo.', 'Cada ser humano es único y valioso, nadie debe ser discriminado...', 'Escribe qué cualidad especial tienes tú que aporta alegría a tu familia.', 'Burlarse de un compañero por sus gustos o dificultades va contra el respeto.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        8 => [
            'lapso' => 1, 'theme' => 'Fracciones Equivalentes, La Fábula y El Suelo',
            'd' => [
                'lunes' => [1, 'La Fábula: Personajes Animales, Personificación y Moraleja', 'Descubre cómo las historias de animales nos enseñan lecciones morales profundas.', 'Una fábula atribuye cualidades humanas a animales (personificación) y deja una enseñanza...', 'Lee una fábula corta e identifica cuál es la moraleja que enseña.', 'La enseñanza moral que deja una fábula se conoce como:', 'Moraleja', ['Moraleja', 'Desenlace', 'Prólogo', 'Introducción']],
                'martes' => [2, 'Fracciones Equivalentes: Amplificación y Simplificación', 'Comprende que 1/2, 2/4 y 4/8 representan exactamente la misma cantidad.', 'Para hallar fracciones equivalentes multiplicamos o dividimos el numerador y denominador por el mismo número...', 'Simplifica la fracción 6/12 hasta su mínima expresión.', '¿Cuál de las siguientes fracciones es equivalente a 1/2?', '3/6', ['3/6', '2/5', '3/8', '1/4']],
                'miercoles' => [13, 'El Suelo: Capas, Tipos de Suelo y Agricultura Sostenible', 'Aprende sobre el humus, la tierra fértil y cómo proteger la tierra del desgaste.', 'El suelo es la capa superficial de la corteza terrestre rica en materia orgánica...', 'Explica por qué no debemos quemar la vegetación para sembrar.', 'La capa más rica en nutrientes y materia orgánica del suelo se llama:', 'Humus', ['Humus', 'Roca madre', 'Arcilla pura', 'Arena seca']],
                'jueves' => [6, 'Recursos Naturales: Renovables y No Renovables en Venezuela', 'Distingue la energía solar y el agua del petróleo y los minerales fósiles.', 'Los renovables se regeneran de forma natural; los no renovables existen en reservas finitas...', 'Clasifica: madera, petróleo, luz solar y oro en renovables o no renovables.', 'El petróleo es un recurso natural no renovable.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Resolución Pacífica de Conflictos: Mediación Escolar', 'Aprende los pasos para calmar un altercado y llegar a un acuerdo donde todos ganen.', 'Un mediador ayuda a las dos partes a escucharse sin insultos para encontrar soluciones...', 'Simula con un hermano un desacuerdo por un juguete y resuélvanlo dialogando.', 'En un conflicto pacífico, el objetivo es destruir al otro y vencer a toda costa.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        9 => [
            'lapso' => 1, 'theme' => 'Operaciones con Fracciones, El Diccionario y La Energía',
            'd' => [
                'lunes' => [1, 'El Diccionario y el Orden Alfabético: Búsqueda Rápida de Palabras', 'Domina la técnica para buscar palabras desconocidas y conocer sus acepciones.', 'El diccionario organiza las palabras según el abecedario. Nos permite saber significado y ortografía...', 'Ordena alfabéticamente: cascada, cardenal, colibrí, canoa y caimán.', 'Para ordenar palabras alfabéticamente que empiezan con la misma letra, nos fijamos en la:', 'Segunda letra', ['Segunda letra', 'Última letra', 'Longitud de la palabra', 'Sílaba tónica']],
                'martes' => [2, 'Suma y Resta de Fracciones con Igual Denominador', 'Suma y resta porciones fácilmente manteniendo el denominador común.', 'Cuando dos fracciones tienen igual denominador, sumamos los numeradores y dejamos el mismo denominador...', 'Calcula: 2/7 + 3/7 y 8/10 - 3/10 en tu cuaderno.', 'El resultado de sumar 3/8 + 2/8 es:', '5/8', ['5/8', '5/16', '6/8', '1/8']],
                'miercoles' => [13, 'La Energía y sus Formas: Cinética, Potencial y Térmica', 'Aprende que la energía no se crea ni se destruye, solo se transforma.', 'La energía cinética es la del movimiento; la energía potencial es la que está almacenada...', 'Da un ejemplo de energía potencial que se transforma en cinética (ej. una pelota al caer).', '¿Qué tipo de energía tiene una roca en lo alto de una montaña antes de rodar?', 'Energía potencial', ['Energía potencial', 'Energía cinética', 'Energía nuclear', 'Energía sonora']],
                'jueves' => [6, 'Símbolos Patrios de Venezuela: Bandera, Escudo e Himno Nacional', 'Conoce el origen histórico de nuestras ocho estrellas y la letra del Gloria al Bravo Pueblo.', 'Francisco de Miranda trajo la bandera tricolor; el Himno fue compuesto por Vicente Salias y Juan José Landaeta...', 'Escribe la primera estrofa del Himno Nacional de Venezuela.', '¿Quién escribió la letra del Himno Nacional "Gloria al Bravo Pueblo"?', 'Vicente Salias', ['Vicente Salias', 'Simón Bolívar', 'Andrés Bello', 'Antonio Guzmán Blanco']],
                'viernes' => [14, 'Autoestima, Metas Personales y Superación Constante', 'Reconoce tus fortalezas y comprende que los errores son escalones para aprender.', 'Tener una sana autoestima es valorarte tal como eres y saber que puedes mejorar cada día...', 'Escribe tres cosas de las que te sientas orgulloso de ti mismo este mes.', 'Cometer un error en una tarea significa que nunca podrás aprender ese tema.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        10 => [
            'lapso' => 1, 'theme' => 'Gran Reto y Evaluación Integral del Lapso 1',
            'd' => [
                'lunes' => [1, 'Comprensión Lectora y Redacción Integral: El Águila Arpía', 'Aplica todas las destrezas de lectura, conectores y acentuación en un texto científico.', 'El águila arpía es una de las aves rapaces más majestuosas de los bosques venezolanos...', 'Lee el texto y redacta un resumen de 4 líneas con su idea principal.', '¿Qué tipo de texto es un artículo que informa sobre la vida del águila arpía?', 'Texto informativo / expositivo', ['Texto informativo / expositivo', 'Fábula ficticia', 'Poema en verso', 'Guion teatral']],
                'martes' => [2, 'Maratón Matemático: Operaciones Combinadas y Fracciones del Lapso 1', 'Demuestra tu soltura sumando, multiplicando y operando fracciones con éxito.', 'Recordamos la jerarquía de las operaciones: primero paréntesis, luego multiplicaciones y divisiones...', 'Resuelve: (25 + 15) x 2 - 10 en tu cuaderno paso a paso.', 'El resultado de la operación (10 x 5) + (20 ÷ 4) es:', '55', ['55', '50', '60', '75']],
                'miercoles' => [13, 'Feria de Ciencias: Resumen de Célula, Plantas y Ecosistemas', 'Consolida los conocimientos biológicos del primer lapso y su impacto ecológico.', 'Hemos aprendido sobre la célula, la fotosíntesis, los ecosistemas y el ciclo del agua...', 'Explica en tus palabras por qué sin plantas no habría vida animal en la Tierra.', 'La fotosíntesis produce glucosa y libera oxígeno al ambiente.', 'Verdadero', ['Verdadero', 'Falso']],
                'jueves' => [6, 'Geografía Integral de Venezuela y Prevención de Riesgos', 'Recorre de norte a sur nuestro país y revisa tus hábitos de autoprotección.', 'Venezuela es un país privilegiado con salida al Caribe y grandes reservas hídricas...', 'Dibuja un mapa esquemático de Venezuela ubicando La Guaira y el río Orinoco.', 'El estado La Guaira cuenta con salida al mar Caribe.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Evaluación de Compromiso y Valores: Mi Proyecto de 5to Grado', 'Reflexiona sobre tu esfuerzo personal, notas y compañerismo en las primeras 10 semanas.', 'Al culminar este primer lapso escolar, miramos con orgullo lo alcanzado y fijamos nuevas metas...', 'Escribe un compromiso formal de estudio para el segundo lapso escolar.', 'La constancia y la disciplina diaria son la clave para ser un estudiante destacado.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],

        // =====================================================================
        // LAPSO 2: PROFUNDIZACIÓN Y APLICACIÓN (Semanas 11 a 20)
        // =====================================================================
        11 => [
            'lapso' => 2, 'theme' => 'Mínimo Común Múltiplo, El Cuento y Sistema Digestivo',
            'd' => [
                'lunes' => [1, 'El Cuento: Personajes Principales y Secundarios en la Trama', 'Aprende a caracterizar a los personajes según sus acciones, palabras y emociones.', 'En los cuentos encontramos personajes principales (protagonistas y antagonistas) y secundarios...', 'Describe al personaje principal de tu libro favorito con 3 adjetivos.', 'El personaje que se opone al protagonista en una historia se llama:', 'Antagonista', ['Antagonista', 'Secundario', 'Narrador', 'Autor']],
                'martes' => [2, 'Mínimo Común Múltiplo (m.c.m.): Descomposición en Factores Primos', 'Aprende a encontrar el múltiplo común más pequeño entre dos o más números.', 'El m.c.m. se calcula tomando los factores primos comunes y no comunes con su mayor exponente...', 'Calcula el m.c.m. entre 6 y 8 en tu cuaderno.', 'El mínimo común múltiplo entre 4 y 6 es:', '12', ['12', '24', '10', '2']],
                'miercoles' => [13, 'El Sistema Digestivo: El Viaje de los Nutrientes por el Cuerpo', 'Conoce cómo la boca, el estómago y los intestinos transforman los alimentos.', 'La digestión comienza en la boca con la saliva, continúa en el estómago y en el intestino delgado se absorben los nutrientes...', 'Dibuja el camino que recorre una manzana desde que la muerdes hasta que alimenta tus células.', '¿En qué órgano del sistema digestivo se absorben la mayor parte de los nutrientes?', 'Intestino delgado', ['Intestino delgado', 'Estómago', 'Boca', 'Esófago']],
                'jueves' => [6, 'Primeros Pobladores de Venezuela: Etnias Indígenas y su Cultura', 'Conoce a los arawacos, caribes y timoto-cuicas y su respeto por la naturaleza.', 'Mucho antes de la llegada de los europeos, diversas comunidades indígenas poblaban nuestro suelo...', 'Menciona una etnia indígena venezolana que habite en la actualidad.', 'Los indígenas timoto-cuicas habitaban principalmente en la región de:', 'Los Andes venezolanos', ['Los Andes venezolanos', 'Los Llanos', 'El Delta del Orinoco', 'La Isla de Margarita']],
                'viernes' => [14, 'La Responsabilidad y el Cumplimiento Oportuno de Tareas', 'Comprende el orgullo de entregar tus asignaciones a tiempo sin que te tengan que recordar.', 'Ser responsable significa asumir las consecuencias de nuestros actos y honrar los compromisos...', 'Escribe cuál es tu estrategia para terminar tus deberes antes de jugar.', 'Hacer las tareas a tiempo nos permite disfrutar de nuestro tiempo libre con tranquilidad.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        12 => [
            'lapso' => 2, 'theme' => 'Fracciones con Distinto Denominador, Uso de B y V, Circulación',
            'd' => [
                'lunes' => [1, 'Ortografía Práctica: El Uso Correcto de la B y la V', 'Domina las reglas para escribir sin errores: bra, bre, bri, terminaciones en -ivo, -iva.', 'Se escribe con B antes de consonante (blanco, brazo) y las terminaciones en -aba...', 'Escribe 5 palabras con B y 5 con V justificando la regla ortográfica.', '¿Cuál de las siguientes palabras está correctamente escrita?', 'Creativo', ['Creativo', 'Creatibo', 'Kreativo', 'Creatiboo']],
                'martes' => [2, 'Suma y Resta de Fracciones con Distinto Denominador mediante m.c.m.', 'Transforma fracciones a un común denominador para sumarlas con facilidad.', 'Para sumar 1/2 + 1/3, hallamos el m.c.m.(2,3)=6, convertimos a 3/6 + 2/6 = 5/6...', 'Resuelve en tu cuaderno: 1/4 + 2/3.', 'Al sumar 1/2 + 1/4, el resultado exacto es:', '3/4', ['3/4', '2/6', '1/8', '2/4']],
                'miercoles' => [13, 'El Sistema Circulatorio: El Corazón y la Sangre que da Vida', 'Descubre cómo late tu corazón miles de veces al día impulsando oxígeno y defensas.', 'El corazón es una bomba muscular que envía sangre rica en oxígeno por las arterias...', 'Mídete el pulso en reposo durante 1 minuto y anota tus pulsaciones.', '¿Cuáles vasos sanguíneos llevan la sangre oxigenada desde el corazón a todo el cuerpo?', 'Arterias', ['Arterias', 'Venas', 'Capilares linfáticos', 'Tráqueas']],
                'jueves' => [6, 'Época Colonial en Venezuela: Cabildos, Haciendas y Ciudades', 'Aprende cómo se fundaron las primeras ciudades venezolanas y la vida en la colonia.', 'Durante la colonia se fundaron ciudades como Coro, Cumaná y Caracas con su plaza mayor y cabildo...', 'Investiga en qué año fue fundada la ciudad de Caracas o La Guaira.', 'El Cabildo colonial era la institución que administraba el gobierno de las:', 'Ciudades', ['Ciudades', 'Naves marinas', 'Selvas vírgenes', 'Montañas']],
                'viernes' => [14, 'El Valor de la Familia: Gratitud y Colaboración en el Hogar', 'Reconoce el sacrificio diario de tus padres para darte educación y amor.', 'La familia es el primer equipo donde aprendemos a amar, perdonar y colaborar...', 'Haz hoy un gesto de ayuda voluntario en tu hogar sin que tus padres te lo pidan.', 'Ayudar en los quehaceres de la casa fomenta el trabajo en equipo familiar.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        13 => [
            'lapso' => 2, 'theme' => 'Multiplicación de Fracciones, Prefijos y Sistema Respiratorio',
            'd' => [
                'lunes' => [1, 'Prefijos y Sufijos: Formación de Nuevas Palabras en Español', 'Descubre cómo añadir partículas transforma el significado de las raíces léxicas.', 'Prefijos como re-, sub-, des- van al inicio; sufijos como -ero, -eza, -ito van al final...', 'Forma 4 palabras nuevas usando los prefijos des- y sub-.', '¿Qué significa el prefijo "sub-" en la palabra submarino?', 'Por debajo de', ['Por debajo de', 'Por encima de', 'Al lado de', 'Lejos de']],
                'martes' => [2, 'Multiplicación de Fracciones: Producto Directo de Numeradores y Denominadores', 'Aprende la operación más sencilla de fracciones multiplicando en línea recta.', 'Para multiplicar fracciones, multiplicamos numerador por numerador y denominador por denominador: (a/b) x (c/d) = (a x c)/(b x d)...', 'Calcula: 2/3 x 4/5 y simplifica el resultado.', 'El resultado de multiplicar 3/4 x 2/5 es:', '6/20 (o 3/10)', ['6/20 (o 3/10)', '5/9', '6/9', '1/2']],
                'miercoles' => [13, 'El Sistema Respiratorio: Pulmones, Alvéolos y el Oxígeno Vital', 'Aprende cómo inhalamos aire limpio y exhalamos dióxido de carbono.', 'El aire entra por la nariz, baja por la tráquea, llega a los bronquios y en los alvéolos ocurre el intercambio gaseoso...', 'Realiza un ejercicio de respiración profunda de 2 minutos antes de estudiar.', '¿En qué estructura microscópica de los pulmones pasa el oxígeno a la sangre?', 'Alvéolos pulmonares', ['Alvéolos pulmonares', 'Fosas nasales', 'Tráquea', 'Laringe']],
                'jueves' => [6, 'El Cacao y el Café: Motores de la Economía Tradicional Venezolana', 'Conoce por qué el cacao venezolano (Chuao) es considerado el mejor del mundo.', 'Durante siglos, el cacao y el café fueron los principales productos de exportación de Venezuela...', 'Investiga qué estados venezolanos son famosos por su producción de cacao.', 'El cacao de Chuao en Venezuela tiene reconocimiento mundial por su alta calidad y aroma.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Solidaridad Comunitaria: El Poder de Ayudar a los Vecinos', 'Descubre cómo una comunidad unida progresa más rápido y vive más segura.', 'Cuando los vecinos se organizan para limpiar una calle o apoyarse en emergencias, la comunidad florece...', 'Escribe una propuesta para mejorar tu vecindario o edificio.', 'La indiferencia hacia los problemas de la comunidad ayuda a solucionarlos más rápido.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        14 => [
            'lapso' => 2, 'theme' => 'División de Fracciones, La Descripción y El Sistema Excretor',
            'd' => [
                'lunes' => [1, 'La Descripción Literaria: Adjetivación Sensorial y Paisajes', 'Aprende a pintar con palabras usando los 5 sentidos en tus redacciones.', 'Describir es decir con detalle cómo es una persona, animal, objeto o paisaje...', 'Describe el mar Caribe visto desde La Guaira usando los sentidos de la vista, oído y olfato.', '¿Qué sentido se estimula principalmente cuando leemos "el aroma dulce del café matutino"?', 'El olfato', ['El olfato', 'La vista', 'El tacto', 'El oído']],
                'martes' => [2, 'División de Fracciones: El Método del Producto Cruzado o Fracción Inversa', 'Descubre cómo dividir fracciones multiplicando en cruz o invirtiendo la segunda fracción.', 'Para dividir (a/b) ÷ (c/d), multiplicamos en cruz: (a x d)/(b x c)...', 'Calcula: 3/4 ÷ 2/5 en tu cuaderno.', 'El resultado de dividir 1/2 ÷ 1/4 es:', '2', ['2', '1/8', '1/6', '4/2 (o 2)']],
                'miercoles' => [13, 'El Sistema Excretor: Riñones, Filtros Naturales e Hidratación', 'Comprende cómo los riñones limpian la sangre y la importancia de beber agua pura.', 'Los riñones filtran la sangre constantemente, eliminando toxinas a través de la orina...', 'Anota cuántos vasos de agua bebes al día y compáralo con los 6-8 recomendados.', '¿Cuál es la función principal de los riñones en el cuerpo humano?', 'Filtrar la sangre y eliminar desechos', ['Filtrar la sangre y eliminar desechos', 'Bombear sangre', 'Digerir grasas', 'Producir bilis']],
                'jueves' => [6, 'Rutas Comerciales Históricas: Puertos de La Guaira y Puerto Cabello', 'Conoce la historia marítima que conectaba a Venezuela con Europa y las Américas.', 'La Guaira era la puerta de entrada a Caracas y el puerto comercial más activo del país...', 'Investiga qué mercancías entraban por el puerto de La Guaira en el siglo XIX.', 'El puerto de La Guaira ha sido históricamente la principal puerta marítima de entrada a Caracas.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'La Perseverancia: Superar las Dificultades sin Rendirse', 'Aprende que los grandes logros requieren intentar una y otra vez con fe y trabajo.', 'Perseverar no es ser terco, sino mantener el esfuerzo constante hacia una meta valiosa...', 'Recuerda algo que al principio te costaba mucho y que hoy dominas con facilidad.', 'Rendirse al primer obstáculo es la mejor manera de alcanzar grandes metas en la vida.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        15 => [
            'lapso' => 2, 'theme' => 'Decimales por 10, 100, 1000, El Diálogo y Nutrición',
            'd' => [
                'lunes' => [1, 'El Diálogo Escrito: El Uso del Guion Largo en Conversaciones', 'Aprende a escribir conversaciones claras distinguiendo la voz de cada interlocutor.', 'El guion largo (—) indica cuándo habla un personaje sin necesidad de repetir constantemente "dijo él"...', 'Escribe un diálogo de 4 intervenciones entre un delfín y una tortuga marina.', '¿Qué signo ortográfico se utiliza para indicar la intervención de un personaje en un diálogo?', 'El guion largo (—)', ['El guion largo (—)', 'El punto final', 'El asterisco', 'La barra inclinada']],
                'martes' => [2, 'Multiplicación y División de Decimales por 10, 100 y 1.000', 'Aprende el truco de correr la coma decimal hacia la derecha o izquierda sin operar largo.', 'Al multiplicar por 10, corremos la coma un lugar a la derecha. Al dividir entre 10, un lugar a la izquierda...', 'Calcula mentalmente: 4,75 x 100 y 38,2 ÷ 10.', 'El resultado de multiplicar 0,65 x 100 es:', '65', ['65', '6,5', '650', '0,065']],
                'miercoles' => [13, 'La Nutrición y el Trompo de los Alimentos de Venezuela', 'Conoce las franjas del trompo nutricional y cómo balancear tu plato diario.', 'El Trompo de los Alimentos venezolano clasifica: carbohidratos (amarillo), vegetales (verde), proteínas (azul), azúcares (gris) y grasas (naranja)...', 'Dibuja el plato de tu almuerzo ideal incluyendo alimentos de las tres franjas principales.', 'En el Trompo de los Alimentos de Venezuela, ¿de qué color es la franja de las hortalizas y frutas?', 'Verde', ['Verde', 'Amarillo', 'Azul', 'Rojo']],
                'jueves' => [6, 'Resistencia Indígena: El Cacique Guaicaipuro y Tamanaco', 'Conoce la valentía de los líderes originarios que defendieron su tierra y libertad.', 'Guaicaipuro logró confederar a diversas tribus caribes para resistir la invasión colonial...', 'Escribe dos cualidades de liderazgo del cacique Guaicaipuro.', 'El cacique Guaicaipuro lideró la resistencia de los pueblos caribes en el valle de Caracas.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Cuidado de la Salud Personal, Higiene y Actividad Física', 'Comprende que mover tu cuerpo y descansar bien te hace más inteligente y feliz.', 'Hacer ejercicio, dormir 8 horas y lavarse las manos previene enfermedades y oxigena el cerebro...', 'Realiza 15 saltos y 10 sentadillas como pausa activa antes de continuar.', 'Dormir al menos 8 horas diarias es fundamental para consolidar la memoria y el aprendizaje.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        16 => [
            'lapso' => 2, 'theme' => 'Ángulos, Leyendas Tradicionales y Microorganismos',
            'd' => [
                'lunes' => [1, 'La Leyenda Tradicional: Mitos y Relatos de la Oralidad Venezolana', 'Conoce relatos como La Sayona o El Silbón y cómo transmiten advertencias populares.', 'Las leyendas combinan hechos reales con elementos fantásticos y se transmiten de generación en generación...', 'Pregunta a un adulto una leyenda tradicional venezolana y anota de qué trata.', 'Las leyendas tradicionales suelen transmitirse principalmente a través de la tradición:', 'Oral', ['Oral', 'Científica', 'Legal', 'Digital']],
                'martes' => [2, 'Geometría: Ángulos y su Clasificación (Agudo, Recto, Obtuso y Llano)', 'Aprende a medir la abertura entre dos semirrectas usando el transportador.', 'Un ángulo recto mide exactamente 90°, el agudo menos de 90°, el obtuso más de 90° y el llano 180°...', 'Identifica 3 ángulos rectos en los muebles u objetos de tu habitación.', '¿Cuánto mide exactamente un ángulo recto?', '90 grados', ['90 grados', '45 grados', '180 grados', '360 grados']],
                'miercoles' => [13, 'Microorganismos: Bacterias, Hongos y Virus en Nuestro Entorno', 'Distingue entre microorganismos benéficos (levaduras, flora intestinal) y patógenos.', 'No todos los microbios son dañinos; muchos producen queso, yogur y protegen nuestro estómago...', 'Explica por qué es vital lavarse las manos antes de comer para evitar bacterias nocivas.', 'Las bacterias del yogur son ejemplos de microorganismos beneficiosos para la digestión.', 'Verdadero', ['Verdadero', 'Falso']],
                'jueves' => [6, 'Movimientos Precursores: La Conspiración de Gual y España en La Guaira (1797)', 'Conoce cómo en La Guaira se encendió una de las primeras chispas de la libertad.', 'Manuel Gual y José María España promovieron un proyecto pionero de igualdad y república en La Guaira...', 'Investiga dónde nació José María España y qué proclamaban sus ordenanzas.', '¿En qué ciudad costera se gestó la conspiración precursora de Gual y España en 1797?', 'La Guaira', ['La Guaira', 'Maracaibo', 'Mérida', 'Barinas']],
                'viernes' => [14, 'Cultura Sísmica: Qué Hacer Antes, Durante y Después de un Temblor', 'Aprende el protocolo de protección que salva vidas ante movimientos telúricos.', 'Agacharse, cubrirse y agarrarse; mantener la calma y no usar ascensores...', 'Ubica el triángulo de la vida o sitio más seguro de tu casa en caso de sismo.', 'Durante un terremoto, la regla de oro es salir corriendo gritando por las escaleras.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        17 => [
            'lapso' => 2, 'theme' => 'Perímetros, Poesía, Rima y Cambios en la Pubertad',
            'd' => [
                'lunes' => [1, 'El Poema: Verso, Estrofa, Rima Asonante y Consonante', 'Aprende a expresar emociones con musicalidad y belleza en el lenguaje poético.', 'Un verso es una línea del poema, un conjunto de versos forma una estrofa. La rima es la igualdad de sonidos finales...', 'Escribe una estrofa de 4 versos que rimen sobre el sol de la mañana.', '¿Cómo se llama cada una de las líneas que componen un poema?', 'Verso', ['Verso', 'Párrafo', 'Capítulo', 'Oración']],
                'martes' => [2, 'Cálculo de Perímetros de Polígonos Regulares e Irregulares', 'Aprende a calcular el contorno de terrenos, canchas y figuras geométricas.', 'El perímetro es la suma de las longitudes de todos los lados de una figura geométrica: P = L1 + L2 + L3 + ...', 'Calcula el perímetro de una cancha rectangular que mide 20 m de largo y 10 m de ancho.', 'El perímetro de un triángulo equilátero cuyos lados miden 5 cm cada uno es:', '15 cm', ['15 cm', '10 cm', '25 cm', '20 cm']],
                'miercoles' => [13, 'La Pubertad: Cambios Físicos y Emocionales en el Crecimiento', 'Comprende que crecer implica cambios normales en tu cuerpo, voz y emociones.', 'La pubertad es la etapa de transición hacia la juventud, guiada por hormonas del crecimiento...', 'Escribe dos cambios físicos normales que ocurren en esta etapa de la vida.', 'Los cambios de humor y el crecimiento rápido durante la pubertad son procesos completamente naturales.', 'Verdadero', ['Verdadero', 'Falso']],
                'jueves' => [6, 'Francisco de Miranda: El Venezolano Más Universal y la Primera Bandera', 'Conoce las hazañas de Miranda en tres continentes y su llegada a La Vela de Coro en 1806.', 'Miranda participó en la Revolución Francesa, la independencia de EE.UU. y trajo el tricolor patrio...', 'Dibuja la bandera traída por Miranda en el buque Leander con sus tres franjas.', '¿Por cuál puerto venezolano desembarcó Francisco de Miranda en 1806 con la bandera tricolor?', 'La Vela de Coro', ['La Vela de Coro', 'Puerto Cabello', 'La Guaira', 'Carúpano']],
                'viernes' => [14, 'Tolerancia y Comunicación Asertiva', 'Aprende a expresar lo que sientes sin agredir y respetando a quien opina distinto.', 'La asertividad es el equilibrio entre ser pasivo y ser agresivo: decir lo que piensas con respeto...', 'Practica con un compañero cómo pedir disculpas con madurez y firmeza.', 'Comunicarse de manera asertiva significa gritar para imponer nuestras razones.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        18 => [
            'lapso' => 2, 'theme' => 'Áreas de Cuadrados y Rectángulos, Verbos y La Fuerza',
            'd' => [
                'lunes' => [1, 'El Verbo: Tiempos Verbales (Presente, Pasado y Futuro) y Modo Indicativo', 'Aprende a conjugar verbos correctamente indicando el momento en que ocurren las acciones.', 'El verbo indica acción, estado o proceso. Varía según la persona (yo, tú, él...) y el tiempo...', 'Conjuga el verbo "estudiar" en presente, pasado y futuro para la persona "nosotros".', 'En la oración "Mañana Alexander y Dimas resolverán el reto", el verbo está en tiempo:', 'Futuro', ['Futuro', 'Presente', 'Pasado', 'Pretérito']],
                'martes' => [2, 'Área de Cuadriláteros: Fórmulas para Cuadrado y Rectángulo', 'Descubre cómo medir superficies en metros y centímetros cuadrados.', 'El área mide el espacio interior. Área del rectángulo = base x altura. Área del cuadrado = lado x lado...', 'Calcula el área de un cuaderno que mide 20 cm de base por 15 cm de altura.', '¿Cuál es el área de un cuadrado cuyos lados miden 6 cm?', '36 cm²', ['36 cm²', '24 cm²', '12 cm²', '30 cm²']],
                'miercoles' => [13, 'La Fuerza, el Movimiento y la Fricción en la Vida Cotidiana', 'Descubre por qué los objetos se mueven, se frenan o cambian de dirección.', 'Una fuerza es un empuje o jalón. La fricción es la fuerza que se opone al movimiento cuando dos superficies se rozan...', 'Frota tus manos rápidamente y siente cómo la fricción genera calor.', '¿Qué fuerza frena a una bicicleta cuando aplicas los frenos en las ruedas?', 'Fuerza de fricción o rozamiento', ['Fuerza de fricción o rozamiento', 'Fuerza de gravedad pura', 'Fuerza magnética', 'Fuerza centrífuga']],
                'jueves' => [6, 'El 19 de Abril de 1810: El Cabildo de Caracas y el Grito de Independencia', 'Conoce cómo el pueblo reunido en la plaza dijo "¡No!" a la dominación española.', 'El sacerdote Madariaga hizo señas al pueblo para rechazar a Emparán, naciendo la Junta Suprema...', 'Escribe por qué el 19 de abril se considera el inicio del proceso emancipador.', '¿En qué ciudad venezolana se produjo el histórico grito del 19 de Abril de 1810?', 'Caracas', ['Caracas', 'Valencia', 'Maracaibo', 'Angostura']],
                'viernes' => [14, 'Cuidado y Ahorro del Agua en el Hogar y la Escuela', 'Comprende que cada gota cuenta y que el agua dulce es un recurso sagrado.', 'Cerrar la llave al cepillarse, reparar fugas y no contaminar ríos protege el futuro...', 'Revisa los grifos de tu casa y asegúrate de que ninguno esté goteando.', 'Dejar correr el agua mientras nos enjabonamos las manos es una excelente práctica de ahorro.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        19 => [
            'lapso' => 2, 'theme' => 'Medidas de Longitud, La Carta y La Luz y el Sonido',
            'd' => [
                'lunes' => [1, 'La Carta y el Correo Electrónico: Estructura, Saludo y Despedida', 'Aprende a comunicarte formal e informalmente con personas lejanas por escrito.', 'Toda carta lleva: lugar y fecha, saludo, cuerpo del mensaje, despedida y firma...', 'Escribe una carta breve a un familiar contándole lo que más te ha gustado aprender este mes.', '¿Cuál elemento va al inicio de una carta formal antes del cuerpo?', 'Lugar, fecha y saludo de apertura', ['Lugar, fecha y saludo de apertura', 'Firma y posdata', 'Despedida informal', 'Índice de materias']],
                'martes' => [2, 'Unidades de Medida de Longitud: Del Milímetro al Kilómetro', 'Aprende a convertir unidades multiplicando o dividiendo por 10 en la escala métrica.', '1 metro = 100 cm = 1.000 mm. 1 kilómetro = 1.000 metros...', 'Convierte 5 kilómetros a metros y 300 centímetros a metros.', '¿Cuántos metros hay exactamente en 3 kilómetros?', '3.000 metros', ['3.000 metros', '300 metros', '30.000 metros', '30 metros']],
                'miercoles' => [13, 'La Luz y el Sonido: Ondas, Reflexión y Refracción', 'Descubre cómo viaja la luz en línea recta y cómo se propaga el sonido en el aire.', 'La luz viaja a 300.000 km/s. Los objetos transparentes la dejan pasar, los opacos producen sombra...', 'Experimenta con una linterna cómo se forma la sombra de tus manos en la pared.', 'La luz viaja mucho más rápido que el sonido; por eso vemos el relámpago antes de oír el trueno.', 'Verdadero', ['Verdadero', 'Falso']],
                'jueves' => [6, 'El 5 de Julio de 1811: La Declaración Solemne de la Independencia', 'Conoce el momento histórico en que Venezuela se proclamó como nación libre y soberana.', 'El Congreso de 1811 firmó el Acta de Independencia redactada por Juan Germán Roscio y Francisco Isnardi...', 'Investiga qué personajes célebres firmaron el Acta de Independencia de Venezuela.', 'El Acta de la Independencia de Venezuela fue firmada solemnemente el:', '5 de Julio de 1811', ['5 de Julio de 1811', '19 de Abril de 1810', '24 de Junio de 1821', '12 de Octubre de 1492']],
                'viernes' => [14, 'Honestidad Académica: El Valor de Hacer tus Propios Trabajos', 'Comprende que copiar en un examen es engañarse a uno mismo y frena tu potencial.', 'Aprender de verdad vale mucho más que obtener una nota ficticia sin saber nada...', 'Escribe por qué te sientes orgulloso cuando resuelves un problema difícil por ti mismo.', 'Copiar las respuestas de otro compañero nos convierte en personas sabias y preparadas.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        20 => [
            'lapso' => 2, 'theme' => 'Gran Reto y Evaluación Integral del Lapso 2',
            'd' => [
                'lunes' => [1, 'Comprensión Lectora y Análisis Crítico: El Orinoco y sus Mitos', 'Integra vocabulario, conectores y figuras literarias en un análisis de texto.', 'El río Orinoco ha inspirado canciones, poemas y mitos milenarios de nuestros ancestros...', 'Escribe un párrafo analizando por qué el río Orinoco es vital para los ecosistemas de Venezuela.', 'Un texto que analiza aspectos reales de un río mezclados con mitos es un texto enriquecido.', 'Verdadero', ['Verdadero', 'Falso']],
                'martes' => [2, 'Evaluación de Geometría, Áreas y Fracciones del Lapso 2', 'Demuestra tu dominio calculando áreas, perímetros y sumas de fracciones mixtas.', 'Aplica todas las fórmulas y pasos aprendidos durante las últimas 10 semanas...', 'Resuelve: Área de un rectángulo de 12 m x 8 m, y suma 2/5 + 1/3.', 'El área de un rectángulo de base 10 cm y altura 7 cm es:', '70 cm²', ['70 cm²', '34 cm²', '17 cm²', '70 cm']],
                'miercoles' => [13, 'Feria Anatómica: Sistemas Digestivo, Circulatorio y Respiratorio', 'Repasa cómo los tres sistemas trabajan en equipo para mantenerte con vida y energía.', 'El digestivo aporta nutrientes, el respiratorio oxígeno y el circulatorio los transporta...', 'Explica en un dibujo cómo el corazón y los pulmones colaboran estrechamente.', 'Los sistemas del cuerpo humano funcionan de manera completamente aislada sin comunicarse.', 'Falso', ['Verdadero', 'Falso']],
                'jueves' => [6, 'El Proceso Emancipador: De Miranda al 5 de Julio de 1811', 'Sintetiza la línea de tiempo de la independencia venezolana.', 'Hemos estudiado a los precursores, el 19 de abril de 1810 y la firma del 5 de julio de 1811...', 'Ordena cronológicamente: Firma del Acta de Independencia, Conspiración de Gual y España, 19 de Abril de 1810.', 'La conspiración de Gual y España ocurrió antes del 19 de Abril de 1810.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Consolidación de Hábitos: Balance del Segundo Lapso Escolar', 'Evalúa tu rendimiento, tus calificaciones y felicítate por tu esfuerzo y crecimiento.', 'Has completado 20 semanas de aprendizaje riguroso. ¡Estás a solo 10 semanas de terminar 5to grado!...', 'Escribe tres metas pedagógicas para el último lapso del año escolar.', 'Celebrar los avances propios y reconocer las áreas de mejora nos ayuda a superarnos.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],

        // =====================================================================
        // LAPSO 3: CONSOLIDACIÓN Y PROYECCIÓN (Semanas 21 a 30)
        // =====================================================================
        21 => [
            'lapso' => 3, 'theme' => 'Medidas de Masa, Pronombres y Temperatura',
            'd' => [
                'lunes' => [1, 'Los Pronombres Personales y la Concordancia en la Oración', 'Aprende a sustituir nombres por pronombres (yo, tú, él, nosotros, ustedes, ellos) sin repetir.', 'Los pronombres reemplazan al sustantivo para evitar repeticiones innecesarias en los textos...', 'Reescribe un párrafo sustituyendo sustantivos repetidos por pronombres personales.', 'En la frase "Alexander y Dimas estudiaron mucho; ellos ganaron XP", la palabra "ellos" es un:', 'Pronombre personal', ['Pronombre personal', 'Sustantivo común', 'Adjetivo calificativo', 'Verbo']],
                'martes' => [2, 'Unidades de Medida de Masa: Gramos, Kilogramos y Toneladas', 'Aprende a pesar ingredientes, cargas y comprender la masa de los objetos.', '1 kilogramo (kg) = 1.000 gramos (g). 1 tonelada (t) = 1.000 kilogramos...', 'Calcula cuántos gramos hay en 2,5 kilogramos de harina.', '¿Cuántos gramos equivalen a 3 kilogramos exactos?', '3.000 gramos', ['3.000 gramos', '300 gramos', '30.000 gramos', '1.500 gramos']],
                'miercoles' => [13, 'El Calor y la Temperatura: Conducción, Convección y Radiación', 'Distingue entre calor (energía en tránsito) y temperatura (medida con termómetro).', 'El calor viaja por conducción en sólidos (cuchara de metal), convección en fluidos y radiación solar...', 'Coloca una cuchara plástica y una de metal en agua tibia y comprueba cuál conduce más calor.', '¿Con cuál instrumento se mide con precisión la temperatura?', 'Termómetro', ['Termómetro', 'Barómetro', 'Balanza', 'Regla métrica']],
                'jueves' => [6, 'La Campaña Admirable (1813): Bolívar y el Título de Libertador', 'Conoce la brillante estrategia militar que cruzó desde Cúcuta hasta Caracas.', 'Simón Bolívar recuperó la república y en Caracas fue aclamado como Libertador por el pueblo...', 'Investiga qué ciudades libertó Bolívar durante la Campaña Admirable de 1813.', 'Simón Bolívar recibió por primera vez el título de Libertador en la ciudad de:', 'Caracas', ['Caracas', 'Bogotá', 'Quito', 'Lima']],
                'viernes' => [14, 'La Paciencia y el Manejo de la Frustración en los Retos Escolares', 'Aprende que cuando algo no sale a la primera, respirar y perseverar es de campeones.', 'Los mejores matemáticos y científicos fallaron cientos de veces antes de triunfar...', 'Escribe una frase motivadora para colocar en tu mesa de estudio.', 'Cometer un error en una operación matemática es una oportunidad de aprendizaje.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        22 => [
            'lapso' => 3, 'theme' => 'Medidas de Capacidad, El Informe Escolar y La Materia',
            'd' => [
                'lunes' => [1, 'El Informe de Investigación Escolar: Estructura y Conclusiones', 'Aprende a presentar datos investigados con orden, rigor y fuentes de información.', 'Un informe académico incluye: portada, introducción, desarrollo del tema, conclusiones y bibliografía...', 'Prepara el esquema de un informe escolar sobre los animales en peligro de extinción en Venezuela.', '¿En qué parte del informe escolar se resumen los hallazgos principales de la investigación?', 'Conclusiones', ['Conclusiones', 'Portada', 'Índice', 'Dedicatoria']],
                'martes' => [2, 'Unidades de Capacidad: Litros, Mililitros y Fracciones de Litro', 'Comprende el volumen de líquidos: medio litro (500 ml), un cuarto de litro (250 ml).', '1 litro (L) = 1.000 mililitros (ml). 1/2 litro = 500 ml; 1/4 litro = 250 ml...', 'Calcula cuántos vasos de 250 ml se pueden llenar con una jarra de 2 litros de jugo.', '¿Cuántos mililitros hay en 1,5 litros de agua pura?', '1.500 ml', ['1.500 ml', '150 ml', '15.000 ml', '500 ml']],
                'miercoles' => [13, 'La Materia y sus Propiedades: Masa, Volumen y Densidad', 'Aprende por qué un barco de acero flota mientras que una pequeña moneda se hunde.', 'La densidad es la relación entre la masa y el volumen de un cuerpo (d = m / v)...', 'Coloca un huevo en agua dulce (se hunde) y luego agrega bastante sal al agua (flota por la densidad).', '¿Qué propiedad física determina si un objeto flota o se hunde en un líquido?', 'La densidad', ['La densidad', 'El color', 'La temperatura ambiente', 'El olor']],
                'jueves' => [6, 'Simón Bolívar: El Ideario del Libertador y su Visión de Unidad', 'Conoce los ideales de educación, libertad y justicia plasmados en la Carta de Jamaica.', 'Bolívar soñaba con una América unida, próspera y con una sólida educación para el pueblo...', 'Lee la célebre frase: "Moral y luces son nuestras primeras necesidades" y explica qué significa.', 'Bolívar afirmó que "Moral y luces son nuestras primeras necesidades".', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Ciudadanía Digital y Uso Seguro de Redes e Internet', 'Aprende a proteger tus datos privados y a navegar con supervisión y prudencia.', 'No compartir contraseñas, no hablar con desconocidos en línea y respetar a otros usuarios en la red...', 'Establece con tus padres una regla familiar para el uso de pantallas y videojuegos.', 'Compartir tu dirección y número de teléfono con extraños en internet es una práctica segura.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        23 => [
            'lapso' => 3, 'theme' => 'Proporcionalidad Directa, La Entrevista y Mezclas',
            'd' => [
                'lunes' => [1, 'La Entrevista Periodística: Preguntas Abiertas y Cerradas', 'Aprende a formular buenas preguntas para conocer la historia de una persona.', 'Las preguntas abiertas invitan a explicar ("¿Cómo te sentiste...?"), las cerradas se responden con sí o no...', 'Entrevista a tu mamá o papá haciéndoles 4 preguntas sobre cómo era su escuela cuando eran niños.', '¿Cuál de las siguientes es una pregunta abierta ideal para una entrevista?', '¿Qué fue lo más emocionante de tu viaje?', ['¿Qué fue lo más emocionante de tu viaje?', '¿Tienes hambre?', '¿Hoy es lunes?', '¿Fuiste al parque?']],
                'martes' => [2, 'Proporcionalidad Directa Básica y Tablas de Variación', 'Comprende cómo al duplicar una cantidad, la otra también se duplica.', 'Si 1 libreta cuesta 3 dólares, 2 libretas costarán 6 dólares, 3 costarán 9 dólares...', 'Elabora una tabla de proporcionalidad para el costo de 1, 2, 4 y 8 cajas de lápices.', 'Si 2 paquetes de galletas cuestan 4 dólares, ¿cuánto costarán 6 paquetes al mismo precio?', '12 dólares', ['12 dólares', '8 dólares', '10 dólares', '16 dólares']],
                'miercoles' => [13, 'Mezclas Homogéneas y Heterogéneas en la Cocina y la Ciencia', 'Distingue disoluciones donde no se ven las fases de aquellas donde sí se ven.', 'En una mezcla homogénea (agua con sal) no se distinguen los componentes; en la heterogénea (ensalada, agua con aceite) sí...', 'Prepara una limonada e identifica si es homogénea o heterogénea.', 'El agua con arena es un ejemplo claro de mezcla:', 'Heterogénea', ['Heterogénea', 'Homogénea', 'Pura elemental', 'Gaseosa simple']],
                'jueves' => [6, 'La Batalla de Carabobo (24 de Junio de 1821): La Gran Victoria', 'Conoce el genio táctico de Bolívar y el heroísmo de Páez y Pedro Camejo (Negro Primero).', 'En las sabanas de Carabobo se selló militarmente la independencia de Venezuela en una hora de combate...', 'Escribe dos líneas sobre el heroísmo del teniente Pedro Camejo en Carabobo.', '¿En qué fecha se libró la gloriosa Batalla de Carabobo?', '24 de Junio de 1821', ['24 de Junio de 1821', '5 de Julio de 1811', '19 de Abril de 1810', '17 de Diciembre de 1830']],
                'viernes' => [14, 'El Trabajo Cooperativo y el Liderazgo Positivo', 'Aprende que el verdadero líder no manda con soberbia sino que sirve e inspira al equipo.', 'Un buen líder escucha a todos, reparte las tareas con justicia y celebra los logros del grupo...', 'Escribe qué cualidades debe tener un capitán de equipo deportivo o de proyecto escolar.', 'El liderazgo positivo busca que todo el equipo triunfe y aprenda unido.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        24 => [
            'lapso' => 3, 'theme' => 'Regla de Tres Simple, El Periódico Escolar y Separación de Mezclas',
            'd' => [
                'lunes' => [1, 'El Periódico Escolar: La Noticia y las Seis Preguntas Clave', 'Aprende a redactar noticias respondiendo: qué, quién, cuándo, dónde, cómo y por qué.', 'La noticia es un texto informativo objetivo de actualidad. El titular debe ser llamativo pero verídico...', 'Redacta una noticia corta sobre un logro escolar o familiar usando las 6 preguntas.', '¿Cuál de las siguientes preguntas NO es una de las preguntas clave del periodismo?', '¿Cuánto dinero tienes en el bolsillo?', ['¿Cuánto dinero tienes en el bolsillo?', '¿Qué ocurrió?', '¿Dónde sucedió?', '¿Quiénes participaron?']],
                'martes' => [2, 'La Regla de Tres Simple Directa: Pasos para Resolver Problemas', 'Domina la fórmula que multiplica en diagonal y divide entre el valor restante.', 'Si a -> b, entonces c -> x. La fórmula es x = (b x c) / a...', 'Resuelve: Si un automóvil recorre 120 km en 2 horas, ¿cuántos km recorrerá en 5 horas a la misma velocidad?', 'Si 3 cuadernos valen 6 dólares, ¿cuánto valen 7 cuadernos?', '14 dólares', ['14 dólares', '12 dólares', '18 dólares', '21 dólares']],
                'miercoles' => [13, 'Métodos de Separación de Mezclas: Filtración, Decantación e Imantación', 'Aprende cómo purifican el agua y separan minerales usando la física.', 'Filtración (sólido de líquido con papel filtro), decantación (líquidos de diferente densidad), imantación (imanes)...', 'Explica cómo separarías una mezcla de arena y clavos de hierro.', '¿Qué método físico usarías para separar limaduras de hierro mezcladas con arena?', 'Imantación con un imán', ['Imantación con un imán', 'Hervir el agua', 'Filtración con colador de tela', 'Evaporación lenta']],
                'jueves' => [6, 'Antonio José de Sucre: El Gran Mariscal de Ayacucho', 'Conoce al prócer cumanés, modelo de lealtad, virtudes militares y diplomacia.', 'Sucre lideró la Batalla de Pichincha y selló la libertad de América del Sur en la Batalla de Ayacucho (1824)...', 'Investiga en qué ciudad venezolana nació Antonio José de Sucre.', 'Antonio José de Sucre nació en la ciudad oriental de:', 'Cumaná', ['Cumaná', 'Barcelona', 'Maturín', 'Margarita']],
                'viernes' => [14, 'Cuidado de los Espacios Públicos y Áreas Verdes', 'Aprende a cuidar plazas, parques y playas porque son patrimonio de todos los ciudadanos.', 'No rayar paredes, no botar basura en las plazas y cuidar los bancos públicos...', 'Recoge hoy cualquier desecho en el parque cercano a tu casa y colócalo en el basurero.', 'Cuidar los parques y plazas es tarea exclusiva de la policía y no de los ciudadanos.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        25 => [
            'lapso' => 3, 'theme' => 'Tablas y Gráficos de Barras, Exposición Oral y La Atmósfera',
            'd' => [
                'lunes' => [1, 'La Exposición Oral: Tono de Voz, Postura y Material de Apoyo', 'Aprende a hablar en público con seguridad, sin miedo escénico y con claridad.', 'Para exponer bien: dominar el tema, modular la voz, mirar al público y preparar láminas limpias y claras...', 'Prepara una micro-exposición de 1 minuto sobre tu animal preferido y preséntala a tu familia.', 'Al exponer en público, es recomendable hablar mirando al suelo sin levantar la voz.', 'Falso', ['Verdadero', 'Falso']],
                'martes' => [2, 'Tablas de Frecuencia y Gráficos de Barras: Interpretación de Datos', 'Aprende a recolectar datos y convertirlos en gráficos visuales fáciles de entender.', 'El eje horizontal muestra las categorías y el eje vertical las frecuencias numéricas...', 'Encuesta a 5 familiares sobre su color preferido y dibuja un gráfico de barras con los resultados.', 'En un gráfico de barras, la barra más alta representa siempre:', 'El dato con mayor frecuencia', ['El dato con mayor frecuencia', 'El dato más barato', 'El dato con menor cantidad', 'El promedio de la clase']],
                'miercoles' => [13, 'La Atmósfera Terrestre y los Fenómenos Meteorológicos', 'Conoce las capas de aire que nos protegen del espacio: troposfera, estratosfera...', 'La troposfera es donde respiramos y ocurren las lluvias; la capa de ozono nos protege de los rayos UV...', 'Dibuja las capas de la atmósfera señalando dónde vuelan los aviones y dónde está la capa de ozono.', '¿En qué capa de la atmósfera ocurren los fenómenos del clima como las lluvias y los vientos?', 'Troposfera', ['Troposfera', 'Termosfera', 'Exosfera', 'Mesosfera']],
                'jueves' => [6, 'Venezuela Agropecuaria: La Vida Campesina y el Valor del Campo', 'Conoce el trabajo de los agricultores y ganaderos que llevan los alimentos a nuestras mesas.', 'Antes del petróleo, Venezuela vivía de la agricultura y hoy debemos revitalizar nuestros campos fértiles...', 'Haz una lista de 5 alimentos que se cosechan en el campo venezolano.', 'Los agricultores y campesinos son fundamentales para la seguridad alimentaria del país.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Ahorro Energético y Consumo Consciente de Electricidad', 'Comprende el impacto ecológico de apagar los bombillos que no se usan.', 'El Guri genera la mayor parte de la energía hidroeléctrica de Venezuela; debemos cuidarla...', 'Apaga los bombillos y artefactos de tu casa que nadie esté utilizando en este momento.', 'Dejar las luces y el televisor encendidos sin que nadie los use ahorra energía.', 'Falso', ['Verdadero', 'Falso']]
            ]
        ],
        26 => [
            'lapso' => 3, 'theme' => 'Promedio Matemático, Textos Instructivos y Contaminación',
            'd' => [
                'lunes' => [1, 'Textos Instructivos: Recetas, Manuales y Secuencia de Pasos', 'Aprende a dar instrucciones precisas y ordenadas usando verbos en infinitivo o imperativo.', 'Un texto instructivo incluye: lista de materiales o ingredientes y pasos ordenados cronológicamente...', 'Escribe la receta instructiva para preparar una arepa venezolana en 4 pasos claros.', '¿Cuál de los siguientes es un ejemplo clásico de texto instructivo?', 'Un manual de armado de un juego', ['Un manual de armado de un juego', 'Un poema de amor', 'Una noticia deportiva', 'Una leyenda antigua']],
                'martes' => [2, 'La Media Aritmética (El Promedio): Cómo se Calcula y su Utilidad', 'Aprende a sumar un conjunto de datos y dividir entre el número total para saber el promedio.', 'Promedio = (Suma de todos los valores) / (Cantidad de valores). Si sacaste 18, 16 y 20: (18+16+20)/3 = 18...', 'Calcula el promedio de las siguientes notas: 16, 18, 14, 20 en tu cuaderno.', 'El promedio de los números 10, 20 y 30 es:', '20', ['20', '15', '25', '30']],
                'miercoles' => [13, 'La Contaminación Ambiental: Aire, Suelo y Agua y las 3 R', 'Aprende a Reducir, Reutilizar y Reciclar para cuidar nuestro hermoso planeta.', 'El plástico tarda siglos en degradarse. Reducir su uso y clasificar los desechos protege los océanos...', 'Identifica en tu hogar un envase plástico que puedan reutilizar creativamente.', '¿Qué significan las tres R de la ecología moderna?', 'Reducir, Reutilizar y Reciclar', ['Reducir, Reutilizar y Reciclar', 'Romper, Reparar y Regalar', 'Reír, Respirar y Rezar', 'Rápido, Recto y Ruidoso']],
                'jueves' => [6, 'Simón Rodríguez: El Gran Maestro del Libertador y Pionero Educativo', 'Conoce al maestro rebelde y visionario que decía: "O inventamos o erramos".', 'Don Simón Rodríguez enseñó a Bolívar a amar la naturaleza, pensar con libertad y educar al pueblo con dignidad...', 'Investiga qué oficios enseñaba Simón Rodríguez en sus escuelas prácticas.', '¿Quién fue el maestro y mentor de Simón Bolívar famoso por la frase "O inventamos o erramos"?', 'Simón Rodríguez', ['Simón Rodríguez', 'Andrés Bello', 'Fray Bartolomé de las Casas', 'Vicente Salias']],
                'viernes' => [14, 'La Gratitud: Agradecer a Padres, Maestros y a la Vida', 'Descubre cómo dar las gracias de corazón abre puertas y genera felicidad interna.', 'Las personas agradecidas son más felices y valoran lo que tienen en lugar de quejarse...', 'Dale hoy las gracias a tus padres con un abrazo sincero por su apoyo en tus estudios.', 'Expresar gratitud sincera fortalece los lazos de amor y confianza en la familia.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        27 => [
            'lapso' => 3, 'theme' => 'Cuerpos Geométricos, Uso de C, S y Z, y Capas de la Tierra',
            'd' => [
                'lunes' => [1, 'Ortografía Crítica: El Uso de la C, la S y la Z en Palabras Similares', 'Aprende las reglas para no dudar entre c, s y z: terminaciones en -ción, -sión, -ez, -eza.', 'Se escribe con Z en aumentativos (-azo, -aza), con C los diminutivos (-cito, -cita) y terminaciones en -ción...', 'Escribe 3 palabras con C, 3 con S y 3 con Z aplicando las reglas explicadas.', '¿Cuál palabra está correctamente escrita?', 'Esperanza', ['Esperanza', 'Esperansa', 'Esperanca', 'Esperanzza']],
                'martes' => [2, 'Cuerpos Geométricos en 3D: Caras, Vértices y Aristas (Cubos y Prismas)', 'Descubre las figuras con volumen que ocupan un lugar en el espacio real.', 'Un cubo tiene 6 caras cuadradas iguales, 8 vértices y 12 aristas...', 'Busca en tu casa una caja de zapatos y cuenta cuántas caras, vértices y aristas tiene.', '¿Cuántas caras tiene exactamente un cubo perfecto?', '6 caras', ['6 caras', '4 caras', '8 caras', '12 caras']],
                'miercoles' => [13, 'Estructura Interna de la Tierra: Corteza, Manto y Núcleo', 'Descubre qué hay debajo de nuestros pies hasta el centro incandescente de la Tierra.', 'La corteza es la cáscara delgada donde vivimos; el manto contiene magma viscoso y el núcleo es de hierro y níquel...', 'Dibuja un corte de la Tierra como si fuera una fruta con piel, pulpa y semilla.', 'La capa más externa y sólida de la Tierra donde se asientan los continentes y océanos es la:', 'Corteza terrestre', ['Corteza terrestre', 'Manto inferior', 'Núcleo interno', 'Atmósfera']],
                'jueves' => [6, 'Tradiciones y Folklore de Venezuela: El Joropo, Parrandas y Danzas', 'Valora la riqueza cultural que llena de alegría y orgullo a nuestras regiones.', 'El joropo con arpa, cuatro y maracas es nuestro baile nacional; en diciembre cantamos parrandas y aguinaldos...', 'Escribe dos tradiciones culturales que se celebren en tu familia en navidades o carnaval.', 'El joropo es considerado el baile y género musical nacional de Venezuela.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Paz y Mediación: Cómo ser un Embajador de Paz en la Escuela', 'Aprende a calmar discusiones y promover un ambiente de respeto donde nadie sea acosado.', 'El bullying se detiene cuando los compañeros valientes no se ríen ni lo permiten...', 'Escribe cómo reaccionarías si ves a un compañero nuevo tímido y apartado en el recreo.', 'Defender a un compañero que está siendo molestado demuestra verdadero coraje y nobleza.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        28 => [
            'lapso' => 3, 'theme' => 'Probabilidad Simple, Redacción de Ensayos y Terremotos',
            'd' => [
                'lunes' => [1, 'El Ensayo Breve: Tesis, Argumentos y Conclusión Personal', 'Aprende a defender tu punto de vista con razones sólidas y fundamentadas.', 'Un ensayo expone una idea (tesis) y la defiende con argumentos lógicos antes de concluir...', 'Escribe un ensayo de 6 líneas defendiendo por qué la lectura diaria nos hace más libres e inteligentes.', 'En un ensayo, las razones que apoyan la opinión del autor se denominan:', 'Argumentos', ['Argumentos', 'Moralejas', 'Versos', 'Rimas']],
                'martes' => [2, 'Probabilidad Simple: Sucesos Seguros, Posibles e Imposibles', 'Descubre la matemática del azar al lanzar una moneda o un dado de 6 caras.', 'Seguro es lo que siempre ocurre, imposible lo que nunca puede ocurrir, posible lo que puede o no ocurrir...', 'Calcula la probabilidad de sacar un número par al lanzar un dado de 6 caras (3 de 6 = 1/2).', 'Al lanzar una moneda al aire, ¿cuál es la probabilidad de que caiga cara?', '1 de 2 (50%)', ['1 de 2 (50%)', '1 de 4 (25%)', '100%', '0%']],
                'miercoles' => [13, 'Tectónica de Placas, Volcanes y Terremotos en el Cinturón de Fuego', 'Comprende por qué se mueven los continentes y se producen los sismos.', 'La corteza terrestre está dividida en grandes placas que flotan sobre el manto y chocan o se separan...', 'Explica por qué en Venezuela hay fallas geológicas como la de San Sebastián y Boconó.', 'Los terremotos se originan por el movimiento y fricción de las placas tectónicas.', 'Verdadero', ['Verdadero', 'Falso']],
                'jueves' => [6, 'Andrés Bello: El Sabio Humanista de Venezuela y América', 'Conoce al maestro de la gramática castellana, poeta y diplomático ilustre.', 'Andrés Bello enseñó a Bolívar, escribió la Gramática del Castellano para América y fundó universidades...', 'Investiga qué libro fundamental de nuestra lengua escribió Andrés Bello.', '¿Cuál ilustre venezolano redactó la célebre Gramática de la Lengua Castellana para uso de los americanos?', 'Andrés Bello', ['Andrés Bello', 'Simón Bolívar', 'Antonio Guzmán Blanco', 'Arturo Uslar Pietri']],
                'viernes' => [14, 'El Valor de la Disciplina: Hacer lo Correcto Aun Cuando Nadie te Mira', 'Comprende que la disciplina vence a la inteligencia cuando la inteligencia se confía.', 'La disciplina es el puente entre las metas que sueñas y los logros que alcanzas...', 'Escribe un hábito disciplinado que vas a mantener durante las vacaciones escolares.', 'La constancia diaria y la disciplina son los mejores aliados del éxito académico.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        29 => [
            'lapso' => 3, 'theme' => 'Acertijos Lógicos, Acentuación Diacrítica y Energías Limpias',
            'd' => [
                'lunes' => [1, 'Acentuación Diacrítica: Distinción entre Monosílabos (Él/El, Tú/Tu, Mí/Mi)', 'Aprende cuándo poner tilde a palabras de una sola sílaba para distinguir su función.', 'Él (pronombre) lleva tilde, el (artículo) no. Tú (pronombre) lleva tilde, tu (posesivo) no...', 'Escribe 3 oraciones usando parejas de monosílabos con y sin tilde diacrítica.', 'En la frase "Tú tienes tu cuaderno", ¿cuál de los dos "tu" lleva tilde diacrítica?', 'El primero (pronombre personal)', ['El primero (pronombre personal)', 'El segundo (posesivo)', 'Ninguno de los dos', 'Ambos']],
                'martes' => [2, 'Razonamiento Lógico Matemático: Sucesiones Numéricas y Acertijos', 'Entrena tu mente descubriendo patrones ocultos y secuencias lógicas.', 'En las sucesiones numéricas buscamos la regla de formación: sumar, multiplicar o alternar...', 'Descubre el número que falta en la serie: 2, 5, 10, 17, 26, ___ (regla: sumar impares).', 'En la serie numérica 3, 6, 12, 24, ¿cuál es el número siguiente?', '48', ['48', '36', '30', '52']],
                'miercoles' => [13, 'Energías Limpias y Renovables: Solar, Eólica e Hidroeléctrica', 'Conoce las tecnologías que no emiten gases de efecto invernadero para cuidar la Tierra.', 'Los paneles solares captan fotones, los aerogeneradores el viento y las presas la fuerza del agua...', 'Explica por qué la energía solar es una fuente inagotable y amigable con el medio ambiente.', '¿Cuál de las siguientes es una fuente de energía limpia y renovable?', 'Energía solar de paneles fotovoltaicos', ['Energía solar de paneles fotovoltaicos', 'Quema de carbón fósil', 'Gasolina de petróleo', 'Plásticos quemados']],
                'jueves' => [6, 'Identidad y Orgullo Nacional: La Riqueza de Ser Venezolano', 'Celebra los paisajes, la gente trabajadora, la inventiva y el calor humano de nuestra patria.', 'Venezuela es rica por sus recursos, pero sobre todo por su gente alegre, solidaria y resiliente...', 'Escribe en 5 líneas qué es lo que más amas de tu país Venezuela.', 'El talento, la amabilidad y la solidaridad son sellos de la identidad venezolana.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Proyecto de Vida: Mis Metas y Sueños para el 6to Grado', 'Visualiza el próximo año escolar como el gran cierre de tu primaria con excelencia.', 'Tener metas claras por escrito te orienta como un faro en la noche para navegar hacia tu futuro...', 'Escribe 3 sueños académicos y personales que deseas conquistar en 6to grado.', 'Escribir nuestras metas y revisarlas con frecuencia nos ayuda a mantenernos enfocados.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ],
        30 => [
            'lapso' => 3, 'theme' => 'Gran Reto Final y Graduación Académica de 5to Grado',
            'd' => [
                'lunes' => [1, 'Gran Reto de Lengua: Comprensión Lectora y Producción Escrita Final', 'Demuestra tu madurez redactando un texto completo con ortografía impecable.', 'Aplica todo lo aprendido en los 3 lapsos: conectores, párrafos, acentuación y coherencia...', 'Escribe una composición de 8 líneas titulada: "Lo que aprendí en 5to grado en Nivelia".', 'Un buen escritor revisa su texto antes de entregarlo para corregir ortografía y coherencia.', 'Verdadero', ['Verdadero', 'Falso']],
                'martes' => [2, 'Olimpiada Matemática de 5to Grado: Fracciones, Geometría y Lógica', 'Resuelve el gran desafío matemático que certifica tu dominio de 5to grado.', 'Operaciones combinadas, áreas, perímetros, mínimo común múltiplo y razonamiento veloz...', 'Resuelve: 3/4 + 1/2 x 2/3 y calcula el área de un cuadrado de 9 cm de lado.', 'El área de un cuadrado de 9 cm de lado es exactamente:', '81 cm²', ['81 cm²', '36 cm²', '18 cm²', '72 cm²']],
                'miercoles' => [13, 'Gran Congreso Científico: Síntesis de Biología y Ciencias de la Tierra', 'Repasa las maravillas de la vida celular, los ecosistemas y la física que rige el cosmos.', 'Has explorado desde la célula microscópica hasta las capas de la atmósfera y la Tierra...', 'Explica a tus padres cuál fue el experimento o tema científico que más te apasionó este año.', 'La curiosidad científica y la observación son el motor de los grandes descubrimientos de la humanidad.', 'Verdadero', ['Verdadero', 'Falso']],
                'jueves' => [6, 'Recorrido Histórico y Geográfico de Honor: De los Aborígenes a Carabobo', 'Sintetiza la epopeya de nuestra nación y la importancia de defender nuestro patrimonio.', 'Desde las civilizaciones indígenas ancestrales hasta los campos inmortales de Carabobo y Ayacucho...', 'Menciona a los tres próceres venezolanos que más admiras y por qué.', 'La independencia de Venezuela fue el resultado del sacrificio de hombres y mujeres valientes.', 'Verdadero', ['Verdadero', 'Falso']],
                'viernes' => [14, 'Juramento de Honor de Dimas: Preparado para Conquistar 6to Grado', 'Celebra con orgullo tu ascenso académico con el diploma de honor de Nivelia.', '¡Felicitaciones Dimas! Con esfuerzo, disciplina y amor por el estudio has culminado con éxito 5to grado...', 'Firma simbólicamente en tu cuaderno tu juramento de honor como futuro estudiante de 6to grado.', 'El conocimiento adquirido con esfuerzo nadie te lo puede quitar; es tu mayor tesoro.', 'Verdadero', ['Verdadero', 'Falso']]
            ]
        ]
    ];

    // Combinar en el array final
    foreach ($temarios5to as $wNum => $data) {
        $weeks[$wNum] = [
            'lapso' => $data['lapso'],
            'lapso_week' => (($wNum - 1) % 10) + 1,
            'theme' => $data['theme'],
            'days' => []
        ];
        foreach ($data['d'] as $day => $dInfo) {
            $weeks[$wNum]['days'][$day] = [
                'subject_id' => $dInfo[0],
                'title' => $dInfo[1],
                'summary' => $dInfo[2],
                'objective' => 'Desarrollar competencias en: ' . $dInfo[1],
                'reading' => $dInfo[3],
                'exercise' => $dInfo[4],
                'xp' => 50,
                'questions' => [
                    [
                        'text' => $dInfo[5],
                        'type' => count($dInfo[7]) == 2 && in_array('Verdadero', $dInfo[7]) ? 'true_false' : 'multiple_choice',
                        'options' => $dInfo[7],
                        'correct' => $dInfo[6],
                        'explanation' => 'Respuesta fundamentada en la lección diaria de 5to grado.',
                        'points' => 10
                    ],
                    [
                        'text' => '¿Lograste completar la lectura y el ejercicio práctico en tu cuaderno?',
                        'type' => 'true_false',
                        'options' => ['Verdadero', 'Falso'],
                        'correct' => 'Verdadero',
                        'explanation' => '¡Excelente! La práctica constante en el cuaderno consolida el aprendizaje duradero.',
                        'points' => 10
                    ]
                ]
            ];
        }
    }

    return $weeks;
}
