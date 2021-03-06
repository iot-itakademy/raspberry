<?php

namespace App\Controller;

use App\Repository\StatisticRepository;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeController extends AbstractController
{
    private HttpClientInterface $client;

    #[Route('/', name: 'home.index')]
    public function index(ChartBuilderInterface $chartBuilder, StatisticRepository $repo): Response
    {
        $chart1 = $chartBuilder->createChart(Chart::TYPE_LINE);
        $dateArray = [];
        $statsArray = [];
        for ($i = 11; $i >= 0; $i-- ) {
            $dateArray[] = date("F-Y", strtotime("-". $i . " months"));
            $valueRepo = $repo->getCountByDate(date("Y-m", strtotime("-". $i . " months")))["sum"];
            $statsArray[] = $valueRepo == null ? 0 : intval($valueRepo);
        }
        $chart1->setData([
            'labels' => $dateArray,
            'datasets' => [
                [
                    'label' => 'Activité de lannée',
                    'backgroundColor' => 'rgb(40, 23, 83)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $statsArray
                ],
            ],
        ]);

        $chart2 = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart2->setData([
            'labels' => ['Intrus', 'Amis', 'Erreur'],
            'datasets' => [
                [
                    'label' => 'Activité de la semaine',
                    'data'=> [300, 50, 100],
                    'backgroundColor' => [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)'
                    ],

                ]
            ]
        ]);
        $params = $this->getCameraParams();
        $params = $params[0];
        $json = json_decode(json_encode($params), true);
        $json = json_decode($json['params'], true);

        return $this->render('home.html.twig', [
            'chart1' => $chart1,
            'chart2' => $chart2,
            'cameraInfo' => $params,
            'cameraParam' => $json
        ]);
    }

    public function __construct(HttpClientInterface $client){
        $this->client = $client;
    }

    public function getCameraParams(): array{
        $response = $this->client->request(
            'GET',
            'http://www.scrutoscope.live/api/settings/camera/1'
        );
        return json_decode($response->getContent());
    }

}
