<?php

namespace Admin\ChartBundle\Controller;

use BaseBundle\Entity\User;
use DateTime;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Zend\Json\Expr;

class MemberChartsController extends Controller
{
    /**
     * @Route("/member", name="admin_member_charts")
     */
    public function getMemberChartsAction(){

        $obj = $this->getGenderPieChart();
        $o = $this->getMemberInscriptionByMonthChart();

        return $this->render('@AdminChart/MemberCharts/member_charts.html.twig',
            array(
                'member_iscription' => $o['chart'],
                'member_gender' => $obj['chart'],
                'total_number' => $obj['total_number'],
                'number_by_city' => $this->getMemberNumberByCity(),
                'cum_number' => $o['chart2']
            ));
    }

    private function containsMonth($counts, $month){
        foreach ($counts as $count){
            if($count['creation_month'] == $month)
                return $count;
        }
        return null;
    }

    private function getMemberInscriptionByMonthChart(){

        $months = array(
            0 => 'January',
            1 => 'February',
            2 => 'March',
            3 => 'April',
            4 => 'May',
            5 => 'June',
            6 => 'July',
            7 => 'August',
            8 => 'September',
            9 => 'October',
            10 => 'November',
            11 => 'December'
        );

        $counts = $this->getDoctrine()->getRepository(User::class)->getCountByMonth();

        $tabs = array();
        $tabs2 = array();
        for($i=1; $i<=12; $i++){
            $count = $this->containsMonth($counts, $i);
            if($i==1){
                array_push($tabs2, (int)$count['total']);
            }else{
                array_push($tabs2, $tabs2[$i-2]+(int)$count['total']);
            }
            if ($count!=null){
                array_push($tabs, (int)$count['total']);
            }else{
                array_push($tabs, 0);
            }
        }
        $series = array(
            array("name" => "Member inscriptions number", "data" => $tabs)
        );
        $ob = new Highchart();
        $ob->chart->renderTo('linechart'); // #id du div où afficher le graphe
        $ob->title->text('Member inscriptions number in '.(new DateTime)->format("Y"));
        $ob->xAxis->title(array('text' => "Months"));
        $ob->yAxis->title(array('text' => "Member inscriptions number"));
        $ob->xAxis->categories($months);
        $ob->series($series);

        $series2 = array(
            array("name" => "Member cumulative number", "data" => $tabs2)
        );
        $ob2 = new Highchart();
        $ob2->chart->renderTo('cumlinechart'); // #id du div où afficher le graphe
        $ob2->title->text('Member cumulative number in '.(new DateTime)->format("Y"));
        $ob2->xAxis->title(array('text' => "Months"));
        $ob2->yAxis->title(array('text' => "Member cumulative number"));
        $ob2->xAxis->categories($months);
        $ob2->series($series2);
        return ['chart' => $ob, 'chart2' => $ob2];
    }

    private function getGenderPieChart(){
        $ob = new Highchart();
        $ob->chart->renderTo('piechart');
        $ob->title->text('Gender Percentage');
        $ob->plotOptions->pie(array(
            'allowPointSelect' => true,
            'cursor' => 'pointer',
            'dataLabels' => array('enabled' => false),
            'showInLegend' => true
        ));
        $stats = $this->getDoctrine()->getRepository(User::class)->getGenderNumber();
        $totalMembers=0;
        foreach($stats as $stat) {
            $totalMembers+=$stat['total'];
        }
        $data= array();
        foreach($stats as $stat) {
            $res=array();
            array_push($res,((int)$stat['gender'])==0?'Female':'Male',(($stat['total']) *100)/$totalMembers);
            //var_dump($stat);
            array_push($data,$res);
        }
        // var_dump($data);
        $ob->series(array(array('type' => 'pie','name' => 'Gender share', 'data' => $data)));
        return ['chart' => $ob, 'total_number' => $totalMembers];
    }

    private function getMemberNumberByCity(){
        $datas = $this->getDoctrine()->getRepository(User::class)->getMembersCountByCity();

        $categories= array();
        $nbMembers=array();
        foreach($datas as $data) {
            array_push($categories,$data['city']);
            array_push($nbMembers,(int)$data['total']);
        }
        $series = array(
            array(
                'name' => 'Members',
                'type' => 'column',
                'color' => '#4572A7',
                'yAxis' => 0,
                'data' => $nbMembers,
            )
        );
        $yData = array(
            array(
                'labels' => array(
                    'formatter' => new Expr('function () { return this.value + "" }'),
                    'style' => array('color' => '#4572A7')
                ),
                'gridLineWidth' => 0,
                'title' => array(
                    'text' => 'Number of members',
                    'style' => array('color' => '#4572A7')
                ),
            ),
        );

        $ob = new Highchart();
        $ob->chart->renderTo('barchart'); // The #id of the divwhere to render the chart
        $ob->chart->type('column');
        $ob->title->text('Number of members by city');
        $ob->xAxis->categories($categories);
        $ob->yAxis($yData);
        $ob->legend->enabled(false);
        /*$formatter = new Expr('function () {
            var unit = {
            "Member": "member(s)",
            }[this.series.name];
            
            return this.x + ": <b>" + this.y + "</b> " + unit;
        }');
        $ob->tooltip->formatter($formatter);*/
        $ob->series($series);

        return $ob;
    }

}
