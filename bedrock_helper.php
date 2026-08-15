<?php
require __DIR__ . '/vendor/autoload.php';

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;

function consultarBedrock(string $prompt): array
{
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
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'inferenceConfig' => [
                'maxTokens' => 250,
                'temperature' => 0.2
            ]
        ]);

        $texto = $result['output']['message']['content'][0]['text'] ?? 'Sin respuesta del modelo.';

        return [
            'ok' => true,
            'respuesta' => $texto
        ];
    } catch (AwsException $e) {
        return [
            'ok' => false,
            'error' => $e->getAwsErrorMessage() ?: $e->getMessage()
        ];
    } catch (Exception $e) {
        return [
            'ok' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
