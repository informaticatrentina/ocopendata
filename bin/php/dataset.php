<?php
require 'autoload.php';

$script = eZScript::instance(array(
    'description' => ( "Ckan dataset tools\n\n" ),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true
));

$script->startup();

$options = $script->getOptions(
    '[id:][show][push][delete][purge][generate_from_class:]',
    '',
    array(
        'generate_from_class' => 'Genera un oggetto dataset in eZ dato p\'identificativo di classe specificato',
        'id' => 'eZ Content Object id',
        'show' => 'Mostra le info sul dataset',
        'push' => 'Salva o aggiorna il dataset in Ckan',
        'delete' => 'Marca il dataset come \'deleted\' in Ckan',
        'purge' => 'Elimina il dataset da Ckan'
    )
);
$script->initialize();
$script->setUseDebugAccumulators(true);

$cli = eZCLI::instance();

try {

    $tools = new OCOpenDataTools();

    $cli->warning('Dump connector settings: ');
    print_r($tools->getSettings());
    $cli->notice();

    if ($options['generate_from_class']) {
        $generator = $tools->getDatasetGenerator();
        if ($generator instanceof OcOpendataDatasetGeneratorInterface) {
            $object = $generator->createFromClassIdentifier($options['generate_from_class']);
        } else {
            throw new Exception( 'Generator not found');
        }
    } else {


        if (!$options['id']) {
            throw new Exception('Specifica un object id');
        }
        $object = $tools->validateObject($options['id']);

        if ($options['show'] || ( !$options['push'] && !$options['delete'] && !$options['purge'] )) {

            $cli->warning('Dump local dataset data:');
            print_r($tools->getConverter()->getDatasetFromObject($object));
            $cli->notice();

            $cli->warning('Dump remote dataset data:');
            $remote = null;
            $datasetId = $tools->getConverter()->getDatasetId($object);
            if ($datasetId) {
                try {
                    $remote = $tools->getClient()->getDataset($datasetId);
                } catch (Exception $e) {
                    $remote = $e->getMessage();
                }

            }
            print_r($remote);
            $cli->notice();
        }

        if ($options['push']) {
            $tools->pushObject($object);
            $cli->warning('Push OK');
        }

        if ($options['delete']) {
            $tools->deleteObject($object);
            $cli->warning('Delete OK');
        }

        if ($options['purge']) {
            $tools->deleteObject($object, true);
            $cli->warning('Purge OK');
        }
    }


    $script->shutdown();
} catch (Exception $e) {
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1;
    $script->shutdown($errCode, $e->getMessage());
}