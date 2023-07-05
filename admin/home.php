<h1 class="text-light">Welcome to <?php echo $_settings->info('name') ?></h1>
<?php
 $sched_arr=array();
?>
<hr>
<div class="container">
  <div class="card">
    <div class="card-body">
    <div class="row justify-content-center">
            <div class="col-2">
                <label for="" class="control-label">Location: </label>
            </div>
            <div class="col-5">
                <select class="select select2" id="location" >
                    <?php 
                    if(isset($_GET['lid']))
                    $lid = $_GET['lid'];
                    $location_qry = $conn->query("SELECT * FROM `location` order by `location` asc");
                    while($row = $location_qry->fetch_assoc()):
                    $lid = isset($lid) ? $lid : $row['id'];
                    if($lid == $row['id']){
                        $max = $row['max_a_day'];
                        $sched = $conn->query("SELECT id,date_sched from `schedules` where location_id = '{$row['id']}' ");
                        while($s = $sched->fetch_assoc()){
                            if(!isset($sched_arr[$s['date_sched']]))
                            $sched_arr[$s['date_sched']] = 0;
                            $sched_arr[$s['date_sched']] += 1;
                        }
                    }
                    ?>
                    <option value="<?php echo $row['id'] ?>" <?php echo $lid == $row['id'] ? 'selected' : '' ?>><?php echo $row['location'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div id="calendar"></div>
    </div>
  </div>
</div>
<script>
    $(function(){
        $('.select2').select2()
        var Calendar = FullCalendar.Calendar;
        var date = new Date()
        var d    = date.getDate(),
            m    = date.getMonth(),
            y    = date.getFullYear()
        var max = parseInt('<?php echo $max ?>');
        var scheds = $.parseJSON('<?php echo json_encode($sched_arr) ?>');
        console.log(scheds)

        var calendarEl = document.getElementById('calendar');

        var calendar = new Calendar(calendarEl, {
                        headerToolbar: {
                            left  : 'prev,next today',
                            center: 'title',
                            right : false
                        },
                        themeSystem: 'bootstrap',
                        //Random default events
                        events:function(event,successCallback){
                            var days = moment(event.end).diff(moment(event.start),'days')
                            var events = []
                           for($i = 0; $i < days ; $i++){
                               if ($i == 0)
                                    date  = moment(event.start);
                                else
                                    date  = moment(event.start).add($i,'days')
                                avail  = !scheds[moment(date).format("YYYY-MM-DD")] ? max : max - parseInt(scheds[moment(date).format("YYYY-MM-DD")]);
                                sched = !scheds[moment(date).format("YYYY-MM-DD")] ? 0 : scheds[moment(date).format("YYYY-MM-DD")];
                                if(moment().subtract(1, 'day').diff(date) < 0 && (moment(date).format('dddd') != 'Saturday' && moment(date).format('dddd') != 'Sunday') && avail > 0){
                                  events.push({
                                            title          : 'Scheduled: '+sched,
                                            start          : moment(date).format("YYYY-MM-DD"),
                                            backgroundColor: 'var(--success)', 
                                            borderColor    : 'var(--light)', 
                                            allDay         : true
                                            });
                                    
                                    events.push({
                                            title          : 'Available: '+avail,
                                            start          : moment(date).format("YYYY-MM-DD"),
                                            backgroundColor: 'var(--primary)', 
                                            borderColor    : 'var(--light)', 
                                            allDay         : true
                                            });
                                    }
                                }
                              console.log(events)
                            successCallback(events)

                        },
                        // eventClick:function(info){
                        //     date = moment(info.event.start).format("YYYY-MM-DD");
                        //     uni_modal("","./addSchedule.php?lid=<?php echo $lid ?>&d="+date,"large")
                        // },
                        editable  : true,
                        selectable: true,
                        selectAllow: function(select) {
                                console.log(moment(select.start).format('dddd'))
                            if(moment().subtract(1, 'day').diff(select.start) < 0 && (moment(select.start).format('dddd') != 'Saturday' && moment(select.start).format('dddd') != 'Sunday'))
                                return true;
                            else
                                return false;
                        }
                        });

                        calendar.render();
                        // $('#calendar').fullCalendar()
        $('#location').change(function(){
            location.href = "./?lid="+$(this).val();
        })
    })
</script>
