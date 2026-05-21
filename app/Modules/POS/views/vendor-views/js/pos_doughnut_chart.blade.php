<script>
         // Convert PHP data into a proper array for ECharts
         let chartData = {!! json_encode(
             $data['branches']->map(function ($b) {
                 return [
                     'name' => $b->name,
                     'value' => $b->totalSales,
                 ];
             }),
         ) !!};
         const colors = [
             "{{ $colors['branch_1_color'] }}", // branch-card-1
             "{{ $colors['branch_2_color'] }}", // branch-card-2
             "{{ $colors['branch_3_color'] }}" // branch-card-3
         ];

         chartData = chartData.map((d, i) => ({
             ...d,
             itemStyle: {
                 color: colors[i % colors.length]
             }
         }));


         function createDonutChart(domId, title, data) {
             var chart = echarts.init(document.getElementById(domId));

             var option = {
                 title: {
                     text: title,
                     left: 'center',
                     top: 10,
                     textStyle: {
                         fontSize: 16,
                         fontWeight: 'bold'
                     }
                 },
                 tooltip: {
                     trigger: 'item',
                     formatter: '{b}<br/>{c} ({d}%)'
                 },
                 legend: {
                     orient: 'horizontal',
                     bottom: 0,
                     data: data.map(d => d.name)
                 },
                 series: [{
                         name: title,
                         type: 'pie',
                         radius: ['40%', '65%'],
                         avoidLabelOverlap: true,
                         label: {
                             show: true,
                             position: 'outside',
                             formatter: '{d}%\n{b}',
                             fontSize: 12,
                             fontWeight: 'bold'
                         },
                         labelLine: {
                             length: 45,
                             length2: 25,
                             smooth: false
                         },
                         data: data,
                         animationType: 'scale',
                         animationEasing: 'elasticOut',
                         animationDelay: function(idx) {
                             return idx * 200;
                         }
                     },
                     {
                         // Inner background ring
                         type: 'pie',
                         radius: ['25%', '40%'],
                         silent: true,
                         label: {
                             show: false
                         },
                         data: [{
                             value: 100,
                             itemStyle: {
                                 color: '#f0f0f0'
                             }
                         }]
                     }
                 ]
             };

             chart.setOption(option);
             return chart;
         }

         // Chart 1
         createDonutChart('chart1', '', chartData);
     </script>