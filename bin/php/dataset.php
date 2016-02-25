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
    '[id:][show][push][delete][purge][generate_from_class:][dry-run]',
    '',
    array(
        'generate_from_class' => 'Genera un oggetto dataset in eZ dato p\'identificativo di classe specificato',
        'dry-run' => 'Verifica l\azione ma non la esegue',
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
            $object = $generator->createFromClassIdentifier($options['generate_from_class'],
                $options['dry-run'] !== null);
            if (!$options['dry-run']) {
                $cli->warning("Generato/aggiornato oggetto " . $object->attribute('id'));
            } else {
                $cli->warning('Ok');
            }
        } else {
            throw new Exception('Generator not found');
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
            if (!$options['dry-run']) {
                $tools->pushObject($object);
                $cli->warning('Push OK');
            } else {
                $cli->warning('Ok');
            }
        }

        if ($options['delete']) {
            if (!$options['dry-run']) {
                $tools->deleteObject($object);
                $cli->warning('Delete OK');
            } else {
                $cli->warning('Ok');
            }
        }

        if ($options['purge']) {
            if (!$options['dry-run']) {
                $tools->deleteObject($object, true);
                $cli->warning('Purge OK');
            } else {
                $cli->warning('Ok');
            }
        }
    }


    $script->shutdown();
} catch (Exception $e) {
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1;
    $script->shutdown($errCode, $e->getMessage());
}