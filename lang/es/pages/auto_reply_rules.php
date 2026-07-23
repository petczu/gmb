<?php

declare(strict_types=1);

return [
    'title' => 'Reglas de respuesta automática con IA',
    'section' => ':stars  ·  reseñas de :rating estrellas',
    'enabled' => 'Respuesta automática activada',
    'mode' => 'Modo',
    'mode_auto' => 'Publicar automáticamente',
    'mode_draft' => 'Borrador para aprobar',
    'tone' => 'Tono / plantilla',
    'tone_placeholder_positive' => 'p. ej. Cercano y agradecido.',
    'tone_placeholder_negative' => 'p. ej. Pedir disculpas y ofrecer una solución.',
    'instruction' => 'Instrucción adicional (opcional)',
    'language' => 'Idioma',
    'language_placeholder' => 'Detectar según la reseña',
    'save_rules' => 'Guardar reglas',
    'rules_saved' => 'Reglas de respuesta guardadas',

    // Blade intro
    'intro' => 'Configura cómo responde la IA a cada puntuación. <strong>Publicar automáticamente</strong> envía la respuesta a Google al instante; <strong>Borrador para aprobar</strong> la manda antes a la cola de Aprobaciones. Cada generación cuenta para la cuota mensual de IA de tu plan.',
];
