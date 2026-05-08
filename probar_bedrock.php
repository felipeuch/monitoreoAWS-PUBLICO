<?php
include("verificar_sesion.php");
require __DIR__ . '/vendor/autoload.php';

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;

date_default_timezone_set('America/Santiago');

$respuesta = "";
$error = "";

try {
    $client = new BedrockRuntimeClient([
        'version' => 'latest',
        'region'  => 'us-east-2'
    ]);

    $result = $client->converse([
        'modelId' => 'us.amazon.nova-lite-v1:0',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'text' => 'Define monitoreo TI en una frase.'
                    ]
                ]
            ]
        ],
        'inferenceConfig' => [
            'maxTokens' => 30,
            'temperature' => 0.2
        ]
    ]);

    $respuesta = $result['output']['message']['content'][0]['text'] ?? 'Sin respuesta del modelo.';
} catch (AwsException $e) {
    $error = $e->getAwsErrorMessage() ?: $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba Bedrock</title>
</head>
<body>
    <h1>Prueba Amazon Bedrock</h1>

    <?php if ($error): ?>
        <p><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></p>
    <?php else: ?>
        <p><strong>Respuesta:</strong> <?php echo htmlspecialchars($respuesta); ?></p>
    <?php endif; ?>
</body>
</html>
