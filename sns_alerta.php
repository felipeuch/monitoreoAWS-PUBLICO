<?php
require __DIR__ . '/vendor/autoload.php';

use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;

function enviarAlertaSNS(string $asunto, string $mensaje): bool
{
    $topicArn = 'arn:aws:sns:us-east-2:345259853623:alertas-monitoreo-proyecto';

    $sns = new SnsClient([
        'version' => '2010-03-31',
        'region'  => 'us-east-2'
    ]);

    try {
        $sns->publish([
            'TopicArn' => $topicArn,
            'Subject'  => $asunto,
            'Message'  => $mensaje
        ]);
        return true;
    } catch (AwsException $e) {
        error_log('Error SNS: ' . $e->getMessage());
        return false;
    }
}
?>
