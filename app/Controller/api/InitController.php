<?php

namespace Controller\api;

use OpenApi\Attributes;
use Repository\StationRepository;
use Repository\StationStatusRepository;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;

class InitController implements ControllerInterface
{
    #[Attributes\Get(
        path: '/api/init',
        operationId: 'initApi',
        summary: 'initAPI',
        description: 'Initialisation de l\'API VELIKO se fait au dédut projet. Elle permet de récupérer les données des stations et des status des stations sur API officielle de VELIB',
    )]
    #[Attributes\Response(
        response: '200',
        description: 'Initialisation de l\'API VELIKO est excutée avec succès',
        content: new Attributes\MediaType(
            mediaType: 'application/json',
            examples: [
                new Attributes\Examples(
                    example: '/api/init',
                    summary: '/api/init',
                    value: [
                        'status' => 'success',
                        'message' => 'API VELIKO is running'
                    ]
                )
            ]
        )
    )]
    #[Attributes\Response(
        response: '500',
        description: 'Erreur interne du serveur',
        content: new Attributes\MediaType(
            mediaType: 'application/json',
            examples: [
                new Attributes\Examples(
                    example: 'error',
                    summary: 'Erreur interne',
                    value: [
                        'status' => 'error',
                        'message' => 'Une erreur interne est survenue, veuillez regarder les logs serveur'
                    ]
                )
            ]
        )
    )]
    public function execute(Request $request): string|null
    {
        header('Content-Type: application/json');

        try {
            (new StationRepository())->truncateTable();
            (new StationStatusRepository())->truncateTable();

            // Fetch data on station_information
            $apiUrlStation = 'https://velib-metropole-opendata.smovengo.cloud/opendata/Velib_Metropole/station_information.json';
            $jsonDataStation = file_get_contents($apiUrlStation);
            if ($jsonDataStation === false) {
                new \Exception("Error fetching data from API VELIB (station_information.json)");
            }
            $dataStation = json_decode($jsonDataStation, true);

            foreach ($dataStation["data"]["stations"] as $item) {
                (new StationRepository())->insert($item);
            }

            // Fetch data on station_status
            $apiUrlStatus = 'https://velib-metropole-opendata.smovengo.cloud/opendata/Velib_Metropole/station_status.json';
            $jsonDataStatus = file_get_contents($apiUrlStatus);
            if ($jsonDataStatus === false) {
                new \Exception("Error fetching data from API VELIB (station_status.json)");
            }
            $dataStatus = json_decode($jsonDataStatus, true);

            foreach ($dataStatus["data"]["stations"] as $item) {
                (new StationStatusRepository())->insert($item);
            }

            $listTest = [
                "status" => "success",
                "message" => "API VELIKO is running"
            ];
        } catch (\Exception $e) {
            http_response_code(500);
            $errorResponse = [
                "status" => "error",
                "message" => $e->getMessage()
            ];
            return json_encode($errorResponse);
        }

        return json_encode($listTest);
    }
}
