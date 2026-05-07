<?php

namespace Database\Seeders;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Support\SessionMeta;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssistantConversationSeeder extends Seeder
{
    private const MESSAGES = [
        ['role' => 'user',      'content' => 'Hola, necesito que me ayudes a organizar el lanzamiento de un nuevo producto SaaS. Tenemos fecha para en 6 semanas.'],
        ['role' => 'assistant', 'content' => "¡Claro, con gusto! Para organizar bien el lanzamiento en 6 semanas necesito entender un poco más el contexto:\n\n1. ¿Qué tipo de producto es? (herramienta B2B, app de consumo, etc.)\n2. ¿Ya tienen lista de espera o audiencia pre-existente?\n3. ¿Cuál es el equipo disponible para el lanzamiento?\n\nCon eso puedo ayudarte a armar un plan realista."],
        ['role' => 'user',      'content' => 'Es una herramienta B2B para equipos de diseño, algo así como Figma pero más enfocado en sistemas de diseño. Tenemos unos 200 en lista de espera y el equipo es de 4 personas: yo (founder), un dev, una diseñadora y un marketero part-time.'],
        ['role' => 'assistant', 'content' => "Perfecto, con ese contexto puedo ayudarte a estructurar bien las 6 semanas. Te propongo dividirlo en tres fases:\n\n**Semanas 1–2: Preparación**\n- Definir métricas de éxito del lanzamiento (activaciones, retención día 7, MRR semana 1)\n- Preparar onboarding y documentación básica\n- Segmentar la lista de espera (identifica tus early adopters más motivados)\n\n**Semanas 3–4: Pre-lanzamiento**\n- Acceso early a los 20–30 usuarios más comprometidos de la lista\n- Iterar basado en feedback crítico\n- Preparar material de marketing y casos de uso\n\n**Semanas 5–6: Lanzamiento**\n- Apertura escalonada a toda la lista de espera\n- Campaña de Product Hunt o canal equivalente\n- Soporte activo y monitoreo\n\n¿Quieres que profundicemos en alguna de las fases?"],
        ['role' => 'user',      'content' => 'Me parece bien. ¿Cómo segmentarías la lista de espera para identificar los early adopters más valiosos?'],
        ['role' => 'assistant', 'content' => "Para segmentar los 200 de la lista de espera, usaría una combinación de señales de intención y fit:\n\n**Señales de intención alta:**\n- Respondieron algún formulario con descripción detallada de su caso de uso\n- Te contactaron directamente por email o redes\n- Compartieron tu landing page o la mencionaron en algún foro\n\n**Fit de cliente ideal:**\n- Trabajan en equipos de diseño de 3+ personas\n- Ya usan herramientas parecidas (Figma, Zeroheight, Supernova)\n- Empresa con presupuesto para herramientas (startup serie A+ o empresa mediana)\n\n**Acción práctica:**\nEnvía un email breve a toda la lista con 3 preguntas de calificación. Algo como:\n1. ¿Cuántas personas hay en tu equipo de diseño?\n2. ¿Qué herramienta usas hoy para gestionar tu sistema de diseño?\n3. ¿Cuál es el mayor dolor que tienes con ella?\n\nLos que respondan en las primeras 48h son tus early adopters. De esos, filtra por fit y tendrás tus 20–30 usuarios ideales para el beta."],
        ['role' => 'user',      'content' => 'Excelente. ¿Y qué métricas debería usar para medir el éxito del lanzamiento? No quiero obsesionarme con vanity metrics.'],
        ['role' => 'assistant', 'content' => "Buena pregunta, y muy importante para no caer en la trampa de los números grandes que no dicen nada. Para un SaaS B2B en fase de lanzamiento, las métricas que realmente importan son:\n\n**Métricas de activación:**\n- % de usuarios que completan el onboarding (llevan a cabo la acción clave del producto en sus primeras 24h)\n- Tiempo hasta la primera acción de valor (cuánto tarda un usuario en \"ver el aha moment\")\n\n**Métricas de retención:**\n- Retención día 7 y día 30 (para B2B, >40% en día 7 es buena señal)\n- DAU/MAU ratio de los usuarios activos\n\n**Métricas de negocio:**\n- Conversión de trial a pago (si aplica)\n- NPS o CSAT a la semana del lanzamiento\n- Número de equipos que invitan a un segundo miembro (señal de valor real)\n\n**Lo que NO medir como éxito:**\n- Total de registros (inflado por curiosidad)\n- Visitas a la landing page\n- Seguidores en redes\n\nDefine con tu equipo cuál es el \"aha moment\" del producto y mide qué % de nuevos usuarios llegan a él en las primeras 24h. Esa métrica sola te dirá más que cualquier otra."],
        ['role' => 'user',      'content' => 'Tiene mucho sentido. Ahora cambiando un poco de tema, ¿me puedes ayudar a pensar cómo estructurar el pricing? Tenemos dudas entre freemium y trial.'],
        ['role' => 'assistant', 'content' => "Para herramientas B2B de diseño, la elección entre freemium y trial depende mucho de dónde está el valor de tu producto y el tamaño del equipo objetivo.\n\n**Argumento para trial gratuito (14–21 días):**\n- Permite dar acceso completo sin limitaciones artificiales\n- Crea urgencia real (el reloj corre)\n- Mejor si el valor del producto se percibe rápido y la curva de adopción es baja\n- Más fácil de monetizar desde el día 1\n\n**Argumento para freemium:**\n- Permite que el producto \"entre\" a una empresa con un usuario individual antes de que se tome decisión de compra grupal\n- Ideal si hay efecto de red (cuantos más usuarios, más valor)\n- Genera datos de uso que ayudan al product-led growth\n- Riesgo: puede canibalizar conversiones si el tier gratuito es demasiado generoso\n\n**Mi recomendación para tu caso:**\nGiven que es B2B para equipos, yo iría con **trial de 21 días sin tarjeta de crédito** al lanzamiento. Las razones:\n1. Reduces la fricción inicial (no piden permiso al finance para experimentar)\n2. Con 6 semanas de runway, necesitas aprender rápido si el modelo funciona\n3. Puedes añadir freemium después; quitar features de un tier gratuito ya establecido es muy difícil\n\n¿Tienes ya pensado el pricing por tiers?"],
        ['role' => 'user',      'content' => 'Estábamos pensando en algo así: plan Team de $49/mes para hasta 5 usuarios y plan Business de $149/mes ilimitado. ¿Te parece razonable?'],
        ['role' => 'assistant', 'content' => "El rango está bien posicionado para B2B de diseño, pero hay algunas cosas que revisaría:\n\n**Lo que funciona:**\n- $49 es accesible para que lo apruebe un lead de diseño sin necesitar a finance\n- El salto a $149 es razonable (3x) y justificable\n- \"Ilimitado\" en el tier alto es un buen gancho\n\n**Lo que cuestionaría:**\n1. **\"Hasta 5 usuarios\"** — en diseño, los equipos suelen ser de 2–4 personas o de 8+, rara vez exactamente 5. Considera si el límite debería ser por seats (y cobrar por seat extra) o por workspace/proyectos\n\n2. **Precio anual** — ¿vas a ofrecer descuento por pago anual? El estándar es 20% off (equivalente a 2 meses gratis). Esto mejora el LTV y reduce churn significativamente\n\n3. **¿Qué diferencia ambos planes?** Más allá del número de usuarios, ¿hay features exclusivas del Business? SSO, roles avanzados, integraciones enterprise, SLA de soporte… eso justifica el salto\n\n**Alternativa a considerar:**\nAlgunos SaaS de este segmento van con pricing por seat: $15–20/seat/mes, mínimo 2 seats. Más simple de entender y escala naturalmente con el crecimiento del equipo.\n\n¿Quieres que te ayude a hacer un modelo simple de proyección de revenue bajo cada esquema?"],
        ['role' => 'user',      'content' => 'Sí, hagamos esa proyección. Asumamos que de los 200 de la lista de espera un 15% convierte a pago en el primer mes.'],
        ['role' => 'assistant', 'content' => "Perfecto, hagamos los números. Con tus supuestos:\n\n**Base:** 200 leads → 30 clientes pagos (15% conversión)\n\nAsumiendo una distribución 70/30 entre Team y Business (lo típico en early stage):\n- 21 cuentas Team × $49 = $1,029/mes\n- 9 cuentas Business × $149 = $1,341/mes\n- **MRR mes 1: ~$2,370**\n- **ARR proyectado: ~$28,440**\n\nSi ofreces anual con 20% off (y asumes que el 40% de clientes elige anual):\n- 12 anuales × promedio $83/mes (ponderado) × 12 = $11,952 upfront\n- 18 mensuales × $92/mes promedio = $1,656/mes recurrente\n- **Caja mes 1 con anuales: ~$13,600**\n\n**Escenario de crecimiento (asumiendo 10% MoM):**\n- Mes 3: ~$2,870 MRR\n- Mes 6: ~$3,800 MRR\n- Mes 12: ~$6,100 MRR\n\n**Para hacer sostenible la operación con 4 personas,** generalmente necesitas ~$15K–20K MRR. Eso implica ~150–200 clientes Team. Con 200 en lista de espera solo llegas a 30, así que el crecimiento post-lanzamiento (SEO, word-of-mouth, Product Hunt) es crítico.\n\n¿Quieres que ajustemos los supuestos o exploremos qué churn rate rompe el modelo?"],
        ['role' => 'user',      'content' => 'Muy útil. Oye, ¿puedes recordar estas decisiones de pricing para cuando las retomemos en otra sesión?'],
        ['role' => 'assistant', 'content' => "Anotado. Guardaré en memoria las decisiones clave de hoy:\n\n- **Modelo:** Trial de 21 días sin tarjeta, sin freemium al lanzamiento\n- **Pricing:** Team $49/mes (hasta 5 usuarios), Business $149/mes (ilimitado)\n- **Descuento anual:** 20% off, objetivo 40% mix anual\n- **Target MRR mes 1:** ~$2,370 con 30 clientes (15% conversión de lista de espera)\n- **Meta sostenibilidad:** ~$15K–20K MRR (~150–200 clientes)\n\nCuando retomemos el tema te traeré este contexto directamente. ¿Algo más que quieras dejar registrado de hoy?"],
        ['role' => 'user',      'content' => 'Perfecto. Ahora quiero pensar en el canal de distribución. Tenemos presencia en Twitter y una newsletter pequeña de 800 suscriptores. ¿Por dónde empezarías?'],
        ['role' => 'assistant', 'content' => "Con esos activos, yo priorizaría así:\n\n**1. Newsletter primero (mayor conversión)**\nTus 800 suscriptores ya te conocen y confiaron en darte su email. La tasa de conversión de newsletter a cliente suele ser 5–10x mayor que redes. Diseña una secuencia de 3 emails:\n- Email 1 (semana 4): \"Está llegando, aquí un sneak peek\"\n- Email 2 (semana 5): \"Acceso anticipado para suscriptores\"\n- Email 3 (día del lanzamiento): \"Ya está aquí, link directo\"\n\n**2. Twitter para amplificación**\nNo para conversión directa, sino para que tus clientes iniciales compartan. El formato que mejor funciona en este nicho:\n- Build in public: comparte el proceso de construcción (métricas reales, decisiones de producto)\n- Thread con el \"por qué construí esto\" el día del lanzamiento\n- Testimonios en tiempo real de los primeros usuarios\n\n**3. Comunidades nicho (alta conversión, bajo esfuerzo)**\nAntes de Product Hunt, busca dónde están tus clientes:\n- Figma Community\n- Design Systems Slack/Discord\n- Reddit r/Design, r/SaaS\n- Spectrum o Linear communities\n\n**Lo que evitaría al inicio:**\nPaid ads — con presupuesto limitado y sin datos de conversión, el CAC será muy alto. Espera a tener retención validada antes de escalar con paid.\n\n¿Tienes acceso a alguna comunidad de diseñadores donde ya seas reconocido?"],
        ['role' => 'user',      'content' => 'Sí, soy moderador en un Slack de diseño de sistemas con 3000 personas. No lo había considerado como canal.'],
        ['role' => 'assistant', 'content' => "Ese Slack es probablemente tu activo más valioso para el lanzamiento. 3,000 personas en una comunidad de diseño de sistemas es exactamente tu ICP (Ideal Customer Profile). Algunas ideas de cómo aprovecharlo sin quemar tu credibilidad como moderador:\n\n**Qué hacer:**\n- Comparte el proceso de construcción de forma genuina en los canales de #tools o #resources, no como promo sino como \"estoy construyendo esto y me gustaría feedback\"\n- Pide a 5–10 miembros que ya conoces que sean tus beta testers y que compartan su experiencia orgánicamente\n- El día del lanzamiento, un anuncio honesto en el canal apropiado: \"Llevo X meses construyendo algo para esta comunidad, hoy lo lanzamos\"\n\n**Qué evitar:**\n- Anuncios repetidos o en múltiples canales del mismo Slack\n- Pedir que compartan/voten directamente (parece spam)\n- Usar tu rol de moderador para dar visibilidad extra al anuncio\n\n**La táctica más poderosa:**\nOrganiza una sesión de feedback en vivo dentro del Slack — algo como \"Design System Tools review: les muestro lo que estoy construyendo, 45 min, feedback bienvenido\". Eso te da exposición orgánica, feedback real y primeros usuarios comprometidos.\n\n¿Has mencionado el proyecto en ese Slack antes?"],
        ['role' => 'user',      'content' => 'Un par de veces de forma muy informal. La gente mostró interés pero no di seguimiento. ¿Cómo recupero ese momentum?'],
        ['role' => 'assistant', 'content' => "Perfectamente recuperable. La gente no recuerda que no diste seguimiento; solo recuerdan si tu producto les resuelve algo. Aquí está cómo retomarlo:\n\n**Mensaje de re-enganche (para el canal o en DMs):**\n\"Hace unos meses comenté que estaba construyendo [X]. Por fin está casi listo. Busco 10 equipos que quieran acceso anticipado a cambio de 30 min de feedback. ¿Alguien interesado?\"\n\nEste formato funciona porque:\n- No pide nada grande (30 min de feedback, no una compra)\n- Crea escasez real (10 equipos, no \"regístrate en la lista\")\n- Es específico sobre lo que ofreces y lo que pides a cambio\n\n**Para los DMs directos a los que mostraron interés antes:**\nEs aún más fácil. \"Oye [nombre], recordé que te interesó [X] cuando lo mencioné. Esta semana abrimos acceso anticipado. ¿Seguís con ese pain de [problema específico que mencionaron]?\"\n\nMencionar el dolor específico que expresaron convierte un mensaje frío en una conversación relevante.\n\n**Timeline:**\nHaz esto en la semana 3 de tu plan (pre-lanzamiento). Así tienes 2 semanas para incorporar su feedback antes del lanzamiento oficial."],
        ['role' => 'user',      'content' => 'Me encanta ese approach. Una duda técnica: ¿en qué tech stack recomendarías construir el onboarding?'],
        ['role' => 'assistant', 'content' => "Para el onboarding de un SaaS B2B en early stage, lo más importante no es el stack sino la velocidad de iteración. Dicho eso, aquí mis recomendaciones por capa:\n\n**Flujo de onboarding (pasos guiados dentro del producto):**\n- **Shepherd.js** o **Driver.js** si quieres algo ligero y personalizable\n- **Userflow** o **Appcues** si prefieres no-code y analytics integrados (tienen free tier suficiente para el lanzamiento)\n- Evita construirlo desde cero — el tiempo no lo justifica\n\n**Email onboarding (secuencia de activación):**\n- **Resend** + templates simples en React Email para los primeros 500 usuarios\n- O **Customer.io** si quieres segmentación por comportamiento desde el día 1 (más setup, más potente)\n\n**Analítica de onboarding:**\n- **PostHog** (self-hosteable, tiene funnel analysis, session recordings, todo lo que necesitas en early stage)\n- Evita GA para producto — no está diseñado para medir activación de SaaS\n\n**Recomendación práctica:**\nDefine primero cuál es tu \"aha moment\" (la acción que predice retención) y diseña el onboarding hacia ese momento. El stack es secundario; he visto onboardings excelentes hechos con Shepherd + emails manuales en la primera semana.\n\n¿Ya identificaron cuál es ese momento en su producto?"],
        ['role' => 'user',      'content' => 'Sí, el aha moment es cuando conectas tu librería de Figma y el sistema detecta automáticamente los tokens de diseño.'],
        ['role' => 'assistant', 'content' => "Ese es un aha moment muy concreto y poderoso — tiene la combinación perfecta de ser técnico pero con resultado visible inmediato. Algunas observaciones:\n\n**Por qué es un buen aha moment:**\n- Es una acción específica (no \"usar el producto\" sino \"conectar Figma\")\n- Tiene un output tangible (tokens detectados = valor inmediato)\n- Diferencia tu producto claramente de competidores que requieren setup manual\n\n**Cómo diseñar el onboarding hacia ese momento:**\n1. **Elimina todo lo que venga antes de ese paso** — no pidas configurar perfil, invitar equipo, o explorar features antes de llegar a la conexión con Figma. Lleva al usuario ahí en el menor número de clics posible\n2. **Celebra el momento** — cuando los tokens se detectan, que haya un estado visual claro (animación, contador, lista de tokens encontrados). Ese es el \"wow\"\n3. **Mide la tasa de llegada** — % de usuarios que completan la conexión de Figma en las primeras 24h. Si está por debajo del 40%, el onboarding tiene fricción que resolver\n\n**Posible fricción a anticipar:**\n- Permisos de Figma (muchos usuarios no saben cómo generar un API token)\n- Librerías muy grandes que tardan en procesarse (añade estado de carga con progreso)\n\n¿La conexión con Figma requiere que el usuario tenga una librería de estilos ya publicada, o funciona con cualquier archivo?"],
        ['role' => 'user',      'content' => 'Funciona con cualquier archivo que tenga estilos locales o variables. Aunque sí es más poderoso con librerías publicadas.'],
        ['role' => 'assistant', 'content' => "Eso es una ventaja competitiva que deberías comunicar más explícitamente. La mayoría de herramientas de design systems asumen que ya tienes una librería publicada y bien estructurada, lo que excluye a equipos más pequeños o en proceso de maduración.\n\n**Cómo aprovecharlo en el onboarding:**\n- En el paso de conexión, muestra dos rutas claramente: \"Tengo una librería publicada\" y \"Tengo estilos en un archivo\"\n- La segunda ruta debería sentirse igual de válida, no como un workaround\n- Mensaje de éxito diferenciado: para estilos locales, algo como \"Detectamos X variables — aquí está tu sistema de diseño base\"\n\n**En el marketing:**\n- \"Funciona desde donde estás\" o \"No necesitas una librería perfecta para empezar\" puede ser un mensaje muy resonante para equipos que sienten que sus design systems están 'work in progress'\n- Ese pain ('no podemos adoptar herramientas enterprise porque nuestro sistema no está maduro') es real en muchos equipos de 3–8 personas\n\n**Segmento a targetear específicamente:**\nEquipos que están en proceso de construir su design system, no los que ya lo tienen establecido. Ellos valoran más la herramienta porque los ayuda a madurar, no solo a documentar.\n\n¿Tienes identificado si tus 200 leads están más en la fase de construcción o de mantenimiento de su design system?"],
        ['role' => 'user',      'content' => 'Buena pregunta, no lo sé con certeza. ¿Cómo lo averiguo rápido?'],
        ['role' => 'assistant', 'content' => "La forma más rápida es una encuesta de 2 preguntas a tu lista de espera. Algo así:\n\n**Pregunta 1:** ¿En qué etapa está el design system de tu equipo?\n- [ ] Acaba de empezar (menos de 6 meses)\n- [ ] En construcción activa (6–18 meses)\n- [ ] Establecido y en mantenimiento (+18 meses)\n- [ ] No tenemos design system todavía\n\n**Pregunta 2:** ¿Cuál es tu mayor reto hoy?\n- [ ] Conseguir que el equipo adopte el design system\n- [ ] Mantenerlo sincronizado con el código\n- [ ] Documentarlo para que otros lo entiendan\n- [ ] Escalar conforme crece el equipo\n\nCon eso en 48–72h tienes la segmentación. Herramienta: Typeform o incluso Google Forms con un email bien redactado.\n\n**Bonus:** Los que respondan son automáticamente tus early adopters más motivados (ya te di este consejo antes, pero aquí lo confirma doblemente). Úsalo para identificar los 20–30 del beta.\n\n¿Quieres que te ayude a redactar el email que acompaña la encuesta?"],
        ['role' => 'user',      'content' => 'Sí por favor, que sea corto y directo.'],
        ['role' => 'assistant', 'content' => "Aquí tienes:\n\n---\n**Asunto:** 2 preguntas rápidas antes del lanzamiento\n\nHola [nombre],\n\nEstás en la lista de espera de [Producto] — gracias por el interés.\n\nAntes de abrir el acceso, quiero asegurarme de que el producto resuelva exactamente lo que necesitas. ¿Me dedicas 60 segundos?\n\n**[→ Responder las 2 preguntas](link)**\n\nQuienes respondan antes del viernes tendrán acceso prioritario en el lanzamiento.\n\nGracias,\n[Tu nombre]\n\n---\n\n**Por qué funciona este email:**\n- Asunto específico (no \"encuesta importante\" ni \"¿tienes 5 min?\")\n- Ancla de tiempo (60 segundos = baja fricción)\n- Incentivo claro y creíble (acceso prioritario, no un descuento genérico)\n- Sin adornos — respeta el tiempo del lector\n\nEnvíalo un martes o miércoles por la mañana, que es cuando las tasas de apertura en B2B son más altas. ¿Necesitas algo más para el lanzamiento?"],
        ['role' => 'user',      'content' => 'Creo que tenemos un plan sólido. ¿Me puedes hacer un resumen ejecutivo de todo lo que discutimos?'],
        ['role' => 'assistant', 'content' => "Claro, aquí está el resumen ejecutivo:\n\n---\n## Plan de lanzamiento — [Producto] · 6 semanas\n\n**Contexto:** Herramienta B2B para equipos de diseño (design systems). 200 leads en lista de espera. Equipo de 4 personas.\n\n**Fase 1 — Semanas 1–2: Preparación**\n- Encuesta de segmentación a los 200 leads (2 preguntas, 48–72h)\n- Identificar 20–30 early adopters (quienes respondan + fit de ICP)\n- Definir y medir el aha moment: conexión de Figma + detección de tokens\n- Preparar onboarding hacia ese momento (Shepherd.js o Userflow)\n\n**Fase 2 — Semanas 3–4: Beta privado**\n- Acceso a los 20–30 seleccionados\n- Sesión de feedback en vivo en el Slack de design systems (3K miembros)\n- Re-enganche a interesados anteriores con oferta de acceso anticipado\n- Iterar onboarding y fricción crítica\n\n**Fase 3 — Semanas 5–6: Lanzamiento**\n- Secuencia de 3 emails a newsletter (800 subs)\n- Apertura escalonada a toda la lista de espera\n- Thread de lanzamiento en Twitter + anuncio en Slack\n\n**Pricing:** Trial 21 días sin tarjeta · Team $49/mes · Business $149/mes · Descuento anual 20%\n\n**Métricas clave:** Activación al aha moment en 24h (objetivo >40%) · Retención día 7 (>40%) · MRR mes 1 (~$2,370 con 15% conversión)\n\n**Meta a 12 meses:** ~$6,100 MRR → camino a $15–20K MRR para sostenibilidad de equipo\n\n---\n\nTodo guardado en memoria. Cuando retomemos cualquier parte del plan lo tendrás disponible directamente. ¡Mucho éxito con el lanzamiento!"],
    ];

    public function run(): void
    {
        $user = User::where('email', 'assistant-pro@test.com')->first();

        if (! $user) {
            $this->command->warn('AssistantConversationSeeder: assistant-pro@test.com not found — run AssistantUserSeeder first.');
            return;
        }

        $conversation = Conversation::firstOrCreate(['user_id' => $user->id]);

        if ($conversation->sessions()->exists()) {
            $this->command->info('AssistantConversationSeeder: conversation already has sessions, skipping.');
            return;
        }

        $startedAt = now()->subDays(3)->setTime(10, 15);

        $session = AssistantSession::create([
            'conversation_id' => $conversation->id,
            'title'           => 'Plan de lanzamiento SaaS — 6 semanas',
            'started_at'      => $startedAt,
            'last_message_at' => $startedAt->copy()->addMinutes(count(self::MESSAGES) * 2),
            'metadata_json'   => null,
        ]);

        $totalInput  = 0;
        $totalOutput = 0;
        $responses   = 0;

        foreach (self::MESSAGES as $i => $msg) {
            $createdAt = $startedAt->copy()->addMinutes($i * 2 + rand(0, 1));

            $inputTokens  = null;
            $outputTokens = null;

            if ($msg['role'] === 'assistant') {
                $inputTokens  = rand(400, 1800);
                $outputTokens = rand(150, 600);
                $totalInput  += $inputTokens;
                $totalOutput += $outputTokens;
                $responses++;
            }

            AssistantMessage::create([
                'conversation_id'  => $conversation->id,
                'session_id'       => $session->id,
                'role'             => $msg['role'],
                'channel'          => 'web',
                'content'          => $msg['content'],
                'memory_processed' => $msg['role'] === 'user',
                'created_at'       => $createdAt,
            ]);
        }

        $meta = SessionMeta::fromArray(null);
        foreach (range(1, count(self::MESSAGES)) as $_) {
            $meta->incrementMessageCount();
        }
        for ($r = 0; $r < $responses; $r++) {
            $meta->addResponse();
        }
        $meta->addCost($totalInput, $totalOutput);

        $session->metadata_json = $meta->toArray();
        $session->save();

        $this->command->info(sprintf(
            'AssistantConversationSeeder: created session #%d with %d messages.',
            $session->id,
            count(self::MESSAGES),
        ));
    }
}
