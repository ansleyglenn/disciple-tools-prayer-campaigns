<?php
class DT_Prayer_Subscription_Management_Magic_Link extends DT_Magic_Url_Base {

    public $post_type = 'subscriptions';
    public $page_title = "My Prayer Times";

    public $magic = false;
    public $parts = [];
    public $root = "subscriptions_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'manage'; // define the type
    public $type_name = 'Subscriptions';
    public $type_actions = [
        '' => "Manage",
        'download_calendar' => 'Download Calendar',
    ];

    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        parent::__construct();
        if ( !$this->check_parts_match() ){
            return;
        }
        $post = DT_Posts::get_post( "subscriptions", $this->parts["post_id"], true, false );
        if ( is_wp_error( $post ) || empty( $post["campaigns"] ) ){
            return;
        }
        if ( $post["lang"] && $post["lang"] !== "en_US" ){
            $lang_code = $post["lang"];
            add_filter( 'determine_locale', function ( $locale ) use ( $lang_code ){
                if ( !empty( $lang_code ) ){
                    return $lang_code;
                }
                return $locale;
            } );
        }
        $this->page_title = __( "My Prayer Times", 'disciple-tools-prayer-campaigns' );

        // add dt_campaign_core to allowed scripts
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        add_action( 'dt_blank_footer', [ $this, 'form_footer' ] );

        // load if valid url
        if ( 'download_calendar' === $this->parts['action'] ) {
            //add_action( 'dt_blank_footer', [ $this, 'display_calendar' ] );
            $this->display_calendar();
            return;
        } else if ( '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'manage_body' ] );
        } else {
            return; // fail if no valid action url found
        }

        // load page elements
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'luxon';
        return $allowed_js;
    }

    public function wp_enqueue_scripts(){
        wp_register_script( 'luxon', 'https://cdn.jsdelivr.net/npm/luxon@2.3.1/build/global/luxon.min.js', false, "2.3.1", true );
        wp_enqueue_script( 'dt_campaign_core', trailingslashit( plugin_dir_url( __DIR__ ) ) . 'post-type/campaign_core.js', [
            'jquery',
            'lodash',
            'luxon'
        ], filemtime( plugin_dir_path( __DIR__ ) . 'post-type/campaign_core.js' ), true );

    }

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        $this->subscriptions_styles_header();
    }
    public function form_footer(){
        $this->subscriptions_javascript_header();
        wp_footer(); // styles controlled by wp_print_styles and wp_print_scripts actions
    }

    public function subscriptions_styles_header(){
        ?>
        <style>
            body {
                background-color: white;
            }
            #content {
                max-width:100%;
                min-height: 300px;
                margin-bottom: 200px;
            }
            #title {
                font-size:1.7rem;
                font-weight: 100;
            }
            #wrapper {
                max-width:1000px;
                margin:0 auto;
                padding: .5em;
                background-color: white;
            }
            /* Chrome, Safari, Edge, Opera */
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            /* Firefox */
            input[type=number] {
                -moz-appearance: textfield;
            }
            select::-ms-expand {
                display: none;
            }

            /* size specific style section */
            @media screen and (max-width: 991px) {
                /* start of large tablet styles */

            }
            @media screen and (max-width: 767px) {
                /* start of medium tablet styles */

            }
            @media screen and (max-width: 479px) {
                /* start of phone styles */
                body {
                    background-color: white;
                }
            }
            .remove-my-prayer-time {
                color: red;
                cursor:pointer;
            }
            .day-selector {
                cursor: pointer;
            }
            #calendar-content .day-cell {
                max-width: 25%;
                float:left;
                text-align: center;
                border: 1px solid lightgray;
                border-top: none;
                border-left: 0.5px;
                border-right: 0.5px;
            }

            .new-day-number {
                margin-top: 18px;
            }

            .progress-bar {
                height:10px;
                background: dodgerblue;
                width:0;
            }
            .progress-bar-container {
                border: 1px solid #bfbfbf;
                margin: 0 auto 12px auto;
                width: 75%;
            }

            .remove-selection {
                /*float: right;*/
                color: white;
                cursor:pointer;
            }

            .day-extra {
                text-align: start;
                padding: 5px 5px;
            }

            .day-cell {
                /*flex-basis: 14%;*/
                text-align: center;
                flex-grow: 0;

            }
            .disabled-calendar-day {
                width:40px;
                height:40px;
                vertical-align: top;
                padding-top:10px;
                color: grey;
            }
            .calendar {
                display: flex;
                flex-wrap: wrap;
                width: 300px;
                margin: auto
            }
            .month-title {
                text-align: center;
                margin-bottom: 0;
            }
            .week-day {
                height: 20px;
                width:40px;
                color: grey;
            }
            #calendar-content h3 {
                margin-bottom: 0;
            }

            #list-day-times td, th {
                padding: 2px 6px
            }

            .day-in-select-calendar {
                color: black;
                display: inline-block;
                height: 40px;
                width: 40px;
                line-height: 0;
                vertical-align: middle;
                text-align: center;
                padding-top: 18px;
            }
            .selected-day {
                background-color: dodgerblue;
                color: white;
                border-radius: 50%;
                border: 2px solid;
            }

            #modal-calendar.small-view {
                display: flex;
                width: 250px;
                flex-wrap: wrap;
                margin: auto;
            }

            .small-view .calendar {
                width: 180px;
            }
            .small-view .month-title {
                width: 50px;
                padding: 0 10px;
                overflow: hidden;
                flex-grow: 1;
            }
            .small-view .calendar .week-day {
                height: 20px;
                width: 25px;
            }
            .small-view .calendar .day-in-select-calendar {
                height: 25px;
                width: 25px;
            }
            .small-view .calendar .disabled-calendar-day {
                width: 25px;
                height: 25px;
                padding-top: 5px;
                font-size: 11px;
            }

            .small-view .day-in-select-calendar {
                height: 25px;
                width: 25px;
                padding-top: 11px;
                font-size:8px;
            }

            .confirm-view {
                display: none;
            }
            .success-confirmation-section {
                display: none;
            }
            .deleted-time {
                color: grey;
                text-decoration: line-through;
            }
            .timezone-label {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                margin: 12px 0 24px 0;
                text-align: right;
            }
            .timezone-label svg {
                margin-right: 3px;
            }
            .calendar-title {
                margin: 32px auto 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                width: 84%;
            }
            .calendar-title h2{
                color: dodgerblue;
                font-weight: bold;
            }

            /* todo replace 'new_' with '' and unify css new styles with old ones */
            .new_calendar {
                display: flex;
                flex-wrap: wrap;
                justify-content: left;
                width: 92%;
                margin: auto;
            }

            .new_day_cell {
                width: calc(100% / 7);
                height: auto;
                text-align: center;
                cursor: pointer;
            }

            .new_day_cell:hover {
                background-color: #eee;
            }

            .new_weekday {
                width: calc(100% / 7);
                height: auto;
                text-align: center;
                font-weight: bold;
                margin: 12px auto 4px auto;
                border-bottom: 2px solid black;
                border-radius: 0%;
                justify-content: left;
            }

            .prayer-commitment {
                background-color: #57d449;
                border-radius: 5%;
                color: white;
                font-size: 12px;
                margin: 2px auto 2px auto;
                text-align: right;
            }

            .prayer-commitment-text {
                margin-right: 4px;
            }
            .prayer-commitment-tiny {
                background-color: #57d449;
                border-radius: 100%;
                text-indent: 200%;
                white-space: nowrap;
                overflow: hidden;
                width: 12px;
                height: 12px;
                vertical-align: top;
                margin: -10px auto 0 auto;
            }

            #mobile-commitments-container {
                display: none;
            }

            .mobile-commitments {
                display: flex;
                width: 100%;
                display: flex;
                justify-content: space-between;
                vertical-align: middle;
            }

            .mobile-commitments-date {
                color: #0a0a0a;
                border-right: 2px solid darkgray;
                text-align: center;
                width: 25%;
            }

            .mc-day {
                color: darkgray;
            }

            .mc-prayer-commitment-description {
                color: white;
                font-size: 12px;
                margin: 0px auto 0px auto;
                text-align: right;
                width: 75%;
                max-height: 100%;
                padding: 6px;
                vertical-align: middle;
            }

            .mc-prayer-commitment-text {
                width: 100%;
                background-color: #57d449;
                color: white;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 5px;
                margin: 0 0 5px 0;
            }

            .mc-title {
                display: none;
                margin: 50px 0 15px 10px;
                font-size: 32px;
                font-weight: bold;
                text-align: center;
            }

            .mc-description-duration {
                font-size: 125%;
                font-weight: 400;
                margin-left: 15px;
            }

            .mc-description-time {
                font-size: 180%;
                margin-right: 10px;
            }
        </style>
        <?php
    }


    public function get_clean_duration( $start_time, $end_time ) {
        $time_duration = ( $start_time - $end_time ) / 60;

        switch ( true ) {
            case $time_duration < 60:
                $time_duration .= ' minutes';
                break;
            case $time_duration === 60:
                $time_duration = $time_duration / 60 . ' hour';
                break;
            case $time_duration < 60:
                $time_duration = $time_duration . ' hours';
                break;
            case $time_duration > 60:
                $time_duration = $time_duration / 60 . ' hours';
        }
        return $time_duration;
    }

    public function get_timezone_offset( $timezone ) {
        $dt_now = new DateTime();
        $dt_now->setTimezone( new DateTimeZone( esc_html( $timezone ) ) );
        $timezone_offset = sprintf( '%+03d', $dt_now->getOffset() / 3600 );
        return $timezone_offset;
    }

    public function get_download_url() {
        $download_url = trailingslashit( $this->parts['public_key'] ) .'download_calendar';
        return $download_url;
    }

    public function display_calendar() {
        // Get post data
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $campaign_id = $post["campaigns"][0]["ID"];
        $locale = $post["lang"] ?: "en_US";

        //get summary from campaign strings
        $calendar_title = $post['campaigns'][0]['post_title'];
        $campaign = DT_Posts::get_post( "campaigns", $campaign_id, true, false );
        if ( isset( $campaign["campaign_strings"][$locale]["campaign_description"] ) ){
            $calendar_title = $campaign["campaign_strings"][$locale]["campaign_description"];
        } elseif ( isset( $campaign["campaign_strings"]["en_US"]["campaign_description"] ) ){
            $calendar_title = $campaign["campaign_strings"]["en_US"]["campaign_description"];
        }
        $calendar_timezone = $post['timezone'];
        $calendar_dtstamp = gmdate( 'Ymd' ).'T'. gmdate( 'His' ) . "Z";
        $calendar_description = "";
        if ( isset( $campaign["campaign_strings"][$locale]["reminder_content"] ) ){
            $calendar_description = $campaign["campaign_strings"][$locale]["reminder_content"];
        } elseif ( isset( $campaign["campaign_strings"]["en_US"]["reminder_content"] ) ){
            $calendar_description = $campaign["campaign_strings"]["en_US"]["reminder_content"];
        }
        $calendar_timezone_offset = self::get_timezone_offset( esc_html( $calendar_timezone ) );

        $my_commitments_reports = DT_Subscriptions_Management::instance()->get_subscriptions( $this->parts['post_id'] );
        $my_commitments = [];

        foreach ( $my_commitments_reports as $commitments_report ){
            $commitments_report['time_begin'] = $commitments_report['time_begin'] + $calendar_timezone_offset * 3600;
            $commitments_report['time_end'] = $commitments_report['time_end'] + $calendar_timezone_offset * 3600;

            $my_commitments[] = [
                "time_begin" => gmdate( 'Ymd', $commitments_report["time_begin"] ) . 'T'. gmdate( 'His', $commitments_report["time_begin"] ),
                "time_end" => gmdate( 'Ymd', $commitments_report["time_end"] ) . 'T'. gmdate( 'His', $commitments_report["time_end"] ),
                "time_duration" => self::get_clean_duration( $commitments_report["time_end"], $commitments_report["time_begin"] ),
                "location" => $commitments_report['label'],
            ];
        }

        header( 'Content-type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: inline; filename=calendar.ics' );

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//disciple.tools\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "BEGIN:VTIMEZONE\r\n";
        echo "TZID:" . esc_html( $calendar_timezone ) . "\r\n";
        echo "BEGIN:STANDARD\r\n";
        echo "TZNAME:" . esc_html( $calendar_timezone_offset ) . "\r\n";
        echo "TZOFFSETFROM:" . esc_html( $calendar_timezone_offset ) . "00\r\n";
        echo "TZOFFSETTO:" . esc_html( $calendar_timezone_offset ) . "00\r\n";
        echo "DTSTART:19700101T000000\r\n";
        echo "END:STANDARD\r\n";
        echo "END:VTIMEZONE\r\n";

        foreach ( $my_commitments as $mc ) {
            $calendar_uid = md5( uniqid( mt_rand(), true ) ) . "@disciple.tools";

            echo "BEGIN:VEVENT\r\n";
            echo "UID:" . esc_html( $calendar_uid ) . "\r\n";
            echo "DTSTAMP:" . esc_html( $calendar_dtstamp ) . "\r\n";
            echo "SUMMARY:" . esc_html( $calendar_title ) . "\r\n";
            echo "DTSTART:" . esc_html( $mc['time_begin'] ) . "\r\n";
            echo "DTEND:" . esc_html( $mc['time_end'] ) . "\r\n";
            echo "DESCRIPTION:" . esc_html( $calendar_description ) . "\r\n";
            echo "STATUS:CONFIRMED\r\n";
            echo "SEQUENCE:3\r\n";
            echo "BEGIN:VALARM\r\n";
            echo "TRIGGER:-PT10M\r\n";
            echo "ACTION:DISPLAY\r\n";
            echo "END:VALARM\r\n";
            echo "END:VEVENT\r\n";
        }

        echo "END:VCALENDAR\r\n";
        die();
    }

    public function subscriptions_javascript_header(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $campaign_id = $post["campaigns"][0]["ID"];
        $current_commitments = DT_Time_Utilities::get_current_commitments( $campaign_id );
        $my_commitments_reports = DT_Subscriptions_Management::instance()->get_subscriptions( $this->parts['post_id'] );
        $my_commitments = [];
        foreach ( $my_commitments_reports as $commitments_report ){
            $my_commitments[] = [
                "time_begin" => $commitments_report["time_begin"],
                "time_end" => $commitments_report["time_end"],
                "value" => $commitments_report["value"],
                "report_id" => $commitments_report["id"],
                "verified" => $commitments_report["verified"] ?? false,

            ];
        }
        $field_settings = DT_Posts::get_post_field_settings( "campaigns" );
        ?>
        <script>
            let calendar_subscribe_object = [<?php echo json_encode([
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'name' => get_the_title( $this->parts['post_id'] ),
                'translations' => [
                    "select_a_time" => __( 'Select a time', 'disciple-tools-prayer-campaigns' ),
                    "fully_covered_once" => __( 'fully covered once', 'disciple-tools-prayer-campaigns' ),
                    "fully_covered_x_times" => __( 'fully covered %1$s times', 'disciple-tools-prayer-campaigns' ),
                    "time_slot_label" => _x( '%1$s for %2$s minutes.', "Monday 5pm for 15 minutes", 'disciple-tools-prayer-campaigns' ),
                ],
                'my_commitments' => $my_commitments,
                'campaign_id' => $campaign_id,
                'current_commitments' => $current_commitments,
                'start_timestamp' => (int) DT_Time_Utilities::start_of_campaign_with_timezone( $campaign_id ),
                'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $campaign_id ) + 86400,
                'slot_length' => 15,
                'timezone' => $post["timezone"],
                "duration_options" => $field_settings["duration_options"]["default"]
            ]) ?>][0]


            let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
            if ( calendar_subscribe_object.timezone ){
                current_time_zone = calendar_subscribe_object.timezone
            }
            const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)
            let time_slot_coverage = {}
            let selected_calendar_color = 'green'
            let verified = false

            // Function that adds an empty cell to the calendar for each
            // day offset between Sunday and the first day of the campaign
            function add_empty_days( day_offset ) {
                let empty_cells = '';
                for ( var i=0; i<=day_offset; i++ ) {
                    empty_cells += `<div class="new_day_cell"></div>`;
                }
                return empty_cells;
            }

            jQuery(document).ready(function($){

                //set up array of days and time slots according to timezone
                let days = window.campaign_scripts.calculate_day_times(current_time_zone);

                let update_timezone = function (){
                    $('.timezone-current').html(current_time_zone)
                    $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
                }
                update_timezone()
                /**
                 * Draw or refresh the main calendar
                 */
                let draw_calendar = ( id = 'calendar-content') => {
                    let content = $(`#${id}`);
                    content.empty();
                    let list = '';
                    let current_calendar = 1;
                    let new_list = '';
                    let first_cell = true;
                    days.forEach(day=>{
                        if ( first_cell ) {
                            // Write the first month name
                            $('#cal1_title_month').text( window.lodash.escape( day.month ) );

                            // If first campaign weekday isn't a Sunday, add necessary amount of empty cell-days
                            if ( day.weekday != 1 ) {
                                new_list += add_empty_days( day.weekday - 1 ); // 1 is the first day of the week
                            }
                        }

                        // Create a new calendar
                        if ( !first_cell && day.day === "1" ) {
                            // A new month has started, let's append the accumulated days and clear the variable
                            $( '#cal' + current_calendar ).append(new_list);
                            new_list = '';

                            // Add new calendar title after current calendar
                            $( '#cal' + current_calendar ).after(`
                            <div class="calendar-title">
                                <h2>${window.lodash.escape(day.month)}</h2>
                            </div>
                            <div class="new_calendar" id="cal${current_calendar+1}">
                                <div class="new_weekday">S</div>
                                <div class="new_weekday">M</div>
                                <div class="new_weekday">T</div>
                                <div class="new_weekday">W</div>
                                <div class="new_weekday">T</div>
                                <div class="new_weekday">F</div>
                                <div class="new_weekday">S</div>
                            </div>
                            `)
                            //Current calendar is now #cal2
                            current_calendar += 1;

                            // If first weekday of the new month isn't a Sunday, add necessary amount of empty cell-days
                            if ( day.weekday != 1 ) {
                                new_list += add_empty_days( day.weekday - 1 ); // 1 is the first day of the week
                            }
                        }
                        new_list += `
                        <div class="new_day_cell">
                            <div class="new-day-number" data-time="${window.lodash.escape(day.key)}" data-day="${window.lodash.escape(day.key)}">${window.lodash.escape(day.day)}
                                <div><small>${window.lodash.escape(parseInt(day.percent))}%</small></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" data-percent="${window.lodash.escape(day.percent)}" style="width:${window.lodash.escape(parseInt(day.percent))}%"></div>
                                </div>
                                <div class="day-extra" id=calendar-extra-${window.lodash.escape(day.key)}></div>
                            </div>
                        </div>`;
                        first_cell = false;
                    })
                    $( '#cal' + current_calendar ).append(new_list);
                    content.html(`<div class="grid-x" id="selection-grid-wrapper">${list}</div>`)
                }
                draw_calendar()

                /**
                 * Show my commitment under each day
                 */
                let add_my_commitments = ()=>{
                    $('.day-extra').empty()
                    calendar_subscribe_object.my_commitments.forEach(c=>{
                        let time = c.time_begin;
                        let now = new Date().getTime()/1000
                        if ( time >= now ){
                            let day_timestamp = 0
                            days.forEach(d=>{
                                if ( d.key < c.time_begin ){
                                    day_timestamp = d.key
                                }
                            })

                            let date = new Date( time * 1000 );
                            let weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            let day_number = date.getDate();
                            let day_weekday = weekdays[ date.getDay() ];

                            let summary_text = window.campaign_scripts.timestamps_to_summary(c.time_begin, c.time_end, current_time_zone)
                            $(`#calendar-extra-${window.lodash.escape(day_timestamp)}`).append(`
                                <div class="prayer-commitment" id="selected-${window.lodash.escape(time)}"
                                    data-time="${window.lodash.escape(time)}">
                                    <div class="prayer-commitment-text">
                                        ${window.lodash.escape(summary_text)}
                                        <i class="fi-x remove-selection remove-my-prayer-time" data-report="${window.lodash.escape(c.report_id)}" data-time="${window.lodash.escape(time)}" data-day="${window.lodash.escape(day_timestamp)}"></i>
                                    </div>
                                </div>
                            `)
                            $('#mobile-commitments-container').append(`
                                <div class="mobile-commitments" id="mobile-commitment-${window.lodash.escape(time)}">
                                    <div class="mobile-commitments-date">
                                        <div class="mc-day"><b>${window.lodash.escape(day_weekday)}</b></div>
                                        <div class="mc-day">${window.lodash.escape(day_number)}</div>
                                    </div>
                                    <div class="mc-prayer-commitment-description">
                                        <div class="mc-prayer-commitment-text">
                                            <div class="mc-description-duration">${window.lodash.escape(summary_text)}</div>
                                            <div class="mc-description-time"> <i class="fi-x remove-selection remove-my-prayer-time" style="margin-left:6px;" data-report="${window.lodash.escape(c.report_id)}" data-time="${window.lodash.escape(time)}" data-day="${window.lodash.escape(day_timestamp)}"></i></div>
                                        </div>
                                    </div>
                                </div>`)
                        }
                    })
                }
                add_my_commitments()

                /**
                 * Add notice showing that my times have been verified
                 */
                if ( verified ){
                    $("#times-verified-notice").show()
                }

                //change timezone
                $('#confirm-timezone').on('click', function (){
                    current_time_zone = $("#timezone-select").val()
                    update_timezone()
                    days = window.campaign_scripts.calculate_day_times(current_time_zone)
                    draw_calendar()
                    add_my_commitments()
                    draw_modal_calendar()
                })

                /**
                 * Remove a prayer time
                 */
                $(document).on("click", '.remove-my-prayer-time', function (){
                    let x = $(this)
                    let id = x.data("report")
                    let time = x.data('time')
                    x.removeClass("fi-x").addClass("loading-spinner active");
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'delete', parts: calendar_subscribe_object.parts, report_id: id }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    })
                    .done(function(data){
                        $($(`*[data-report=${id}]`)[0].parentElement.parentElement).css({'background-color':'lightgray','text-decoration':'line-through'});
                        $($(`*[data-report=${id}]`)[1].parentElement.parentElement).css({'background-color':'lightgray','text-decoration':'line-through'});
                        x.removeClass("loading-spinner");
                        console.log("adding deleted time" + time);
                        $(`#selected-${time}`).addClass('deleted-time')
                    })
                    .fail(function(e) {
                        console.log(e)
                        jQuery('#error').html(e)
                    })
                })


                /**
                 * Modal for displaying on individual day
                 */
                $('.new-day-number').on( 'click', function (){
                    let day_timestamp = $(this).data('day')
                    $('#view-times-modal').foundation('open')
                    let list_title = jQuery('#list-modal-title')
                    let day=days.find(k=>k.key===day_timestamp)
                    list_title.empty().html(`<h2 class="section_title">${window.lodash.escape(day.formatted)}</h2>`)
                    let day_times_content = $('#day-times-table-body')
                    let times_html = ``
                    let row_index = 0
                    day.slots.forEach(slot=>{
                        let background_color = 'white'
                        if ( slot.subscribers > 0) {
                            background_color = '#1e90ff75'
                        }
                        if ( row_index === 0 ){
                            times_html += `<tr><td>${window.lodash.escape(slot.formatted)}</td>`
                        }
                        times_html +=`<td style="background-color:${background_color}">
                            ${window.lodash.escape(slot.subscribers)} <i class="fi-torsos"></i>
                        </td>`
                        if ( times_html === 3 ){
                            times_html += `</tr>`
                        }
                        row_index = row_index === 3 ? 0 : row_index + 1;
                    })
                    day_times_content.empty().html(`<div class="grid-x"> ${times_html} </div>`)
                })



                function days_for_locale(localeName = 'en-US', weekday = 'long') {
                    let now = new Date()
                    const format = new Intl.DateTimeFormat(localeName, { weekday }).format;
                    return [...Array(7).keys()]
                    .map((day) => format(new Date().getTime() - ( now.getDay() - day  ) * 86400000 ));
                }
                let week_day_names = days_for_locale(navigator.language, 'narrow')
                let headers = `
                  <div class="day-cell week-day">${week_day_names[0]}</div>
                  <div class="day-cell week-day">${week_day_names[1]}</div>
                  <div class="day-cell week-day">${week_day_names[2]}</div>
                  <div class="day-cell week-day">${week_day_names[3]}</div>
                  <div class="day-cell week-day">${week_day_names[4]}</div>
                  <div class="day-cell week-day">${week_day_names[5]}</div>
                  <div class="day-cell week-day">${week_day_names[6]}</div>
                `


                /**
                 * daily prayer time screen
                 */
                let daily_time_select = $('#cp-daily-time-select')

                let select_html = `<option value="false">${calendar_subscribe_object.translations.select_a_time}</option>`

                let coverage = {}
                days.forEach(val=> {
                    let day = val.key
                    for ( const key in calendar_subscribe_object.current_commitments ){
                        if (!calendar_subscribe_object.current_commitments.hasOwnProperty(key)) {
                            continue;
                        }
                        if ( key >= day && key < day + 24 * 3600 ){
                            let mod_time = key % (24 * 60 * 60)
                            let time_formatted = '';
                            if ( window.campaign_scripts.processing_save[mod_time] ){
                                time_formatted = window.campaign_scripts.processing_save[mod_time]
                            } else {
                                time_formatted = window.campaign_scripts.timestamp_to_time( parseInt(key), current_time_zone )
                                window.campaign_scripts.processing_save[mod_time] = time_formatted
                            }
                            if ( !coverage[time_formatted]){
                                coverage[time_formatted] = [];
                            }
                            coverage[time_formatted].push(calendar_subscribe_object.current_commitments[key]);
                        }
                    }
                })
                let key = 0;
                let start_of_today = new Date()
                start_of_today.setHours(0,0,0,0)
                let start_time_stamp = start_of_today.getTime()/1000
                while ( key < 24 * 3600 ){
                    let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+key)
                    let text = ''
                    let fully_covered = window.campaign_scripts.time_slot_coverage[time_formatted] ? window.campaign_scripts.time_slot_coverage[time_formatted] === number_of_days : false;
                    let level_covered = coverage[time_formatted] ? Math.min(...coverage[time_formatted]) : 0
                    if ( fully_covered && level_covered > 1  ){
                        text = `(${calendar_subscribe_object.translations.fully_covered_x_times.replace( '%1$s', level_covered)})`
                    } else if ( fully_covered ) {
                        text = `(${calendar_subscribe_object.translations.fully_covered_once})`
                    }
                    select_html += `<option value="${window.lodash.escape(key)}">
                      ${window.lodash.escape(time_formatted)} ${ window.lodash.escape(text) }
                  </option>`
                    key += calendar_subscribe_object.slot_length * 60
                }
                daily_time_select.empty();
                daily_time_select.html(select_html)

                let duration_options_html = ``
                for (const prop in calendar_subscribe_object.duration_options) {
                    if (calendar_subscribe_object.duration_options.hasOwnProperty(prop) && parseInt(prop) >= parseInt(calendar_subscribe_object.slot_length) ) {
                        duration_options_html += `<option value="${window.lodash.escape(prop)}">${window.lodash.escape(calendar_subscribe_object.duration_options[prop].label)}</option>`
                    }
                }
                $(".cp-time-duration-select").html(duration_options_html)

                daily_time_select.on("change", function (){
                    $('#cp-confirm-daily-times').attr('disabled', false)
                })

                let selected_times = [];
                $('#cp-confirm-daily-times').on("click", function (){
                    let daily_time_selected = parseInt($("#cp-daily-time-select").val());
                    let duration = parseInt($("#cp-prayer-time-duration-select").val())

                    let start_time = days[0].key + daily_time_selected;
                    let start_date = window.luxon.DateTime.fromSeconds(start_time).setZone(current_time_zone)
                    let now = new Date().getTime()/1000
                    for ( let i = 0; i < days.length; i++){
                        let time_date = start_date.plus({day:i})
                        let time = parseInt( time_date.toFormat('X') );
                        let time_label = time_date.toFormat('MMMM dd HH:mm a');
                        let already_added = selected_times.find(k=>k.time===time)
                        if ( !already_added && time > now && time >= calendar_subscribe_object['start_timestamp'] ) {
                            selected_times.push({time: time, duration: duration, label: time_label})
                        }
                    }
                    submit_times();
                })



                /**
                 * Individual prayer times screen
                 */
                let current_time_selected = $("cp-individual-time-select").val();

                //build the list of individually selected times
                let display_selected_times = function (){
                    let html = ""
                    selected_times.sort((a,b)=>{
                        return a.time - b.time
                    });
                    selected_times.forEach(time=>{
                        html += `<li>
                            ${calendar_subscribe_object.translations.time_slot_label.replace( '%1$s', time.label).replace( '%2$s', time.duration )}
                            <button class="remove-prayer-time-button" data-time="${time.time}">x</button>
                        </li>`

                    })
                    $('.cp-display-selected-times').html(html)
                }
                $(document).on( 'click', '.remove-prayer-time-button', function (){
                  let time = parseInt($(this).data('time'))
                  selected_times = selected_times.filter(t=>parseInt(t.time) !== time)
                  display_selected_times()
                })
                //add a selected time to the array
                $('#cp-add-prayer-time').on("click", function(){
                    current_time_selected = $("#cp-individual-time-select").val();
                    let duration = parseInt($("#cp-individual-prayer-time-duration-select").val())
                    let time_label = window.campaign_scripts.timestamp_to_format( current_time_selected, { month: "long", day: "numeric", hour:"numeric", minute: "numeric" }, current_time_zone)
                    let now = new Date().getTime()/1000
                    let already_added = selected_times.find(k=>k.time===current_time_selected)
                    if ( !already_added && current_time_selected > now && current_time_selected >= calendar_subscribe_object['start_timestamp'] ){
                        $('#cp-time-added').show().fadeOut(1000)
                        selected_times.push({time: current_time_selected, duration: duration, label: time_label })
                    }
                    display_selected_times()
                    $('#cp-confirm-individual-times').attr('disabled', false)
                })

                //dawn calendar in date select view
                let modal_calendar = $('#day-select-calendar')
                let now = new Date().getTime()/1000
                let draw_modal_calendar = ()=> {
                    let last_month = "";
                    modal_calendar.empty()
                    let list = ''
                    let mobile_list = ''
                    days.forEach(day => {
                        if (day.month!==last_month) {
                            if (last_month) {
                                //add extra days at the month end
                                let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
                                if ( day_number !== 0 ) {
                                    for (let i = 1; i <= 7 - day_number; i++) {
                                        list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(i)}</div>`
                                    }
                                }
                                list += `</div>`
                            }

                            list += `<h3 class="month-title">${window.lodash.escape(day.month)}</h3><div class="calendar">`
                            if (!last_month) {
                                list += headers
                            }

                            //add extra days at the month start
                            let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
                            let start_of_week = window.campaign_scripts.start_of_week(day.key, current_time_zone);
                            for (let i = 0; i < day_number; i++) {
                                list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(start_of_week.getDate() + i)}</div>`
                            }
                            last_month = day.month
                        }
                        let disabled = (day.key + (24 * 3600)) < now;
                        list += `
                            <div class="day-cell ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}" data-day="${window.lodash.escape(day.key)}">
                                ${window.lodash.escape(day.day)}
                            </div>
                        `
                    })
                    modal_calendar.html(list)
                }
                draw_modal_calendar()

                //when a day is clicked on from the calendar
                $(document).on('click', '.day-in-select-calendar', function (){
                    $('#day-select-calendar div').removeClass('selected-day')
                    $(this).toggleClass('selected-day')
                    //get day and build content
                    let day_key = parseInt($(this).data("day"))
                    let day=days.find(k=>k.key===day_key);
                    //set time key on add button
                    $('#cp-add-prayer-time').data("day", day_key).attr('disabled', false)

                    //build time select
                    let select_html = ``;
                    day.slots.forEach(slot=> {
                        let text = ``
                        if ( slot.subscribers===1 ) {
                            text = "(covered once)";
                        }
                        if ( slot.subscribers > 1 ) {
                            text = `(covered ${slot.subscribers} times)`;
                        }
                        select_html += `<option value="${window.lodash.escape(slot.key)}" ${ (slot.key%(24*3600)) === (current_time_selected%(24*3600)) ? "selected" : '' }>
                            ${window.lodash.escape(slot.formatted)} ${window.lodash.escape(text)}
                        </option>`
                    })
                    $('#cp-individual-time-select').html(select_html).attr('disabled', false)
                })


                $('#cp-confirm-individual-times').on( 'click', function (){
                    submit_times();
                })

                let submit_times = function(){
                    let submit_button = $('.submit-form-button')
                    submit_button.addClass( 'loading' )
                    let data = {
                        action: 'add',
                        selected_times,
                        parts: calendar_subscribe_object.parts
                    }
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    })
                    .done(function(response){
                        $('.hide-on-success').hide();
                        submit_button.removeClass('loading')
                        $('#modal-calendar').hide()

                        $(`.success-confirmation-section`).show()
                        calendar_subscribe_object.my_commitments = response
                        add_my_commitments()
                        submit_button.prop('disabled', false)
                    })
                    .fail(function(e) {
                        console.log(e)
                        $('#selection-error').empty().html(`<div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
                        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>
                        </div>`).show()
                        $('#error').html(e)
                        submit_button.removeClass('loading')
                    })
                }
                $('.close-ok-success').on("click", function (){
                    window.location.reload()
                })


                $('#allow_notifications').on('change', function (){
                    let selected_option = $(this).val();
                    $('.notifications_allowed_spinner').addClass('active')
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({parts: calendar_subscribe_object.parts, allowed:selected_option==="allowed"}),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type + '/allow-notifications',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    }).done(function(){
                        $('.notifications_allowed_spinner').removeClass('active')
                    })
                    .fail(function(e) {

                    })
                })

                /**
                 * Delete profile
                 */
                $('#confirm-delete-profile').on('click', function (){
                    let spinner = $(this)
                    let wrapper = jQuery('#wrapper')
                    jQuery.ajax({
                        type: "DELETE",
                        data: JSON.stringify({parts: calendar_subscribe_object.parts}),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type + '/delete_profile',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    }).done(function(){
                        wrapper.empty().html(`
                            <div class="center">
                            <h1>Your profile has been deleted!</h1>
                            <p>Thank you for praying with us.<p>
                            </div>
                        `)
                        spinner.removeClass('active')
                        $(`#delete-profile-modal`).foundation('close')
                    })
                    .fail(function(e) {
                        console.log(e)
                        $('#confirm-delete-profile').toggleClass('loading')
                        $('#delete-account-errors').empty().html(`<div class="grid-x"><div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>

                        </div></div>`)
                        $('#error').html(e)
                        spinner.removeClass('active')
                    })
                })

                /**
                 * Display mobile commitments if screen dimension is narrow
                 */
                if ( innerWidth < 475 ) {
                        $( '.prayer-commitment' ).attr( 'class', 'prayer-commitment-tiny' );
                        $( '.mc-title' ).show();
                        $( '#mobile-commitments-container' ).show();
                }
            })
        </script>
        <?php
        return true;
    }

    public function manage_body(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( !isset( $post["campaigns"][0]["ID"] ) ){
            return false;
        }
        $campaign_id = $post["campaigns"][0]["ID"];
        //cannot manage a subscription that has no campaign
        if ( empty( $campaign_id ) ){
            $this->error_body();
            exit;
        }


        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        if ( is_wp_error( $campaign ) ) {
            return $campaign;
        }
        ?>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title"><?php esc_html_e( 'My Prayer Times', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    <i><?php echo esc_html( $post["name"] ); ?></i>
                </div>
            </div>
            <div id="times-verified-notice" style="display:none; padding: 20px; background-color: lightgreen; border-radius: 5px; border: 1px green solid; margin-bottom: 20px;">
                <?php esc_html_e( 'Your prayer times have been verified!', 'disciple-tools-prayer-campaigns' ); ?>
            </div>
            <div class="calendar-title">
                <h2 id="cal1_title_month"></h2>
                <div class="timezone-label">
                    <svg height='16px' width='16px' fill="#000000" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"><path d="M50,13c20.4,0,37,16.6,37,37S70.4,87,50,87c-20.4,0-37-16.6-37-37S29.6,13,50,13 M50,5C25.1,5,5,25.1,5,50s20.1,45,45,45  c24.9,0,45-20.1,45-45S74.9,5,50,5L50,5z"></path><path d="M77.9,47.8l-23.4-2.1L52.8,22c-0.1-1.5-1.3-2.6-2.8-2.6h-0.8c-1.5,0-2.8,1.2-2.8,2.7l-1.6,28.9c-0.1,1.3,0.4,2.5,1.2,3.4  c0.9,0.9,2,1.4,3.3,1.4h0.1l28.5-2.2c1.5-0.1,2.6-1.3,2.6-2.9C80.5,49.2,79.3,48,77.9,47.8z"></path></svg>
                    <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                </div>
            </div>

            <div class="new_calendar" id="cal1">
                <div class="new_weekday">S</div>
                <div class="new_weekday">M</div>
                <div class="new_weekday">T</div>
                <div class="new_weekday">W</div>
                <div class="new_weekday">T</div>
                <div class="new_weekday">F</div>
                <div class="new_weekday">S</div>
            </div>

            <!-- Reveal Modal Timezone Changer-->
            <div id="timezone-changer" class="reveal tiny" data-reveal>
                <h2>Change your timezone:</h2>
                <select id="timezone-select">
                    <?php
                    $selected_tz = 'America/Denver';
                    if ( !empty( $selected_tz ) ){
                        ?>
                        <option id="selected-time-zone" value="<?php echo esc_html( $selected_tz ) ?>" selected><?php echo esc_html( $selected_tz ) ?></option>
                        <option disabled>----</option>
                        <?php
                    }
                    $tzlist = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
                    foreach ( $tzlist as $tz ){
                        ?><option value="<?php echo esc_html( $tz ) ?>"><?php echo esc_html( $tz ) ?></option><?php
                    }
                    ?>
                </select>
                <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                </button>
                <button class="button" type="button" id="confirm-timezone" data-close>
                    <?php echo esc_html__( 'Select', 'disciple-tools-prayer-campaigns' )?>
                </button>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>


            <div class="center">
                <button class="button" data-open="daily-select-modal" id="open-select-times-button" style="margin-top: 10px">
                    <?php esc_html_e( 'Add a Daily Prayer Time', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
                <button class="button" data-open="select-times-modal" id="open-select-times-button" style="margin-top: 10px">
                    <?php esc_html_e( 'Add Individual Prayer Times', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
                <a class="button" style="margin-top: 10px" target="_blank" href="<?php echo esc_attr( self::get_download_url() ); ?>"><?php esc_html_e( 'Download Calendar', 'disciple-tools-prayer-campaigns' ); ?></a>
            </div>
            <h3 class="mc-title">My commitments</h3>
            <div id="mobile-commitments-container">
            </div>
            <div class="reveal" id="daily-select-modal" data-reveal>
                <label>
                    <strong><?php esc_html_e( 'Prayer Time', 'disciple-tools-prayer-campaigns' ); ?></strong>
                    <select id="cp-daily-time-select">
                        <option><?php esc_html_e( 'Daily Time', 'disciple-tools-prayer-campaigns' ); ?></option>
                    </select>
                </label>
                <label>
                    <strong><?php esc_html_e( 'For how long', 'disciple-tools-prayer-campaigns' ); ?></strong>
                    <select id="cp-prayer-time-duration-select" class="cp-time-duration-select"></select>
                </label>
                <p class="timezone-label">
                    <svg height='16px' width='16px' fill="#000000" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"><path d="M50,13c20.4,0,37,16.6,37,37S70.4,87,50,87c-20.4,0-37-16.6-37-37S29.6,13,50,13 M50,5C25.1,5,5,25.1,5,50s20.1,45,45,45  c24.9,0,45-20.1,45-45S74.9,5,50,5L50,5z"></path><path d="M77.9,47.8l-23.4-2.1L52.8,22c-0.1-1.5-1.3-2.6-2.8-2.6h-0.8c-1.5,0-2.8,1.2-2.8,2.7l-1.6,28.9c-0.1,1.3,0.4,2.5,1.2,3.4  c0.9,0.9,2,1.4,3.3,1.4h0.1l28.5-2.2c1.5-0.1,2.6-1.3,2.6-2.9C80.5,49.2,79.3,48,77.9,47.8z"></path></svg>
                    <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                </p>

                <div class="success-confirmation-section">
                    <div class="cell center">
                        <h2><?php esc_html_e( 'Your new prayer times have been saved.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    </div>
                </div>


                <div class="center hide-on-success">
                    <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                    </button>

                    <button disabled id="cp-confirm-daily-times" class="cp-nav button submit-form-button loader">
                        <?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?>
                    </button>
                </div>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="center">
                    <button class="button success-confirmation-section close-ok-success" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'ok', 'disciple-tools-prayer-campaigns' )?>
                    </button>
                </div>

            </div>

            <div class="reveal" id="view-times-modal" data-reveal data-close-on-click="true">
                <h3 id="list-modal-title"></h3>

                <div id="list-day-times">
                    <table>
                        <thead>
                        <tr>
                            <th></th>
                            <th>:00</th>
                            <th>:15</th>
                            <th>:30</th>
                            <th>:45</th>
                        </tr>
                        </thead>
                        <tbody id="day-times-table-body">

                        </tbody>
                    </table>
                </div>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="center">
                    <button class="button" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Close', 'disciple-tools-prayer-campaigns' )?>
                    </button>
                </div>
            </div>

            <div class="reveal" id="select-times-modal" data-reveal data-close-on-click="false" data-multiple-opened="true">

                <h2 id="individual-day-title" class="cp-center">
                    <?php esc_html_e( 'Select a day and choose a time', 'disciple-tools-prayer-campaigns' ); ?>
                </h2>
                <div id="cp-day-content" class="cp-center" >
                    <div style="margin-bottom: 20px">
                        <div id="day-select-calendar" class=""></div>
                    </div>
                    <label>
                        <strong><?php esc_html_e( 'Select a prayer time', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        <select id="cp-individual-time-select" disabled style="margin: auto">
                            <option><?php esc_html_e( 'Daily Time', 'disciple-tools-prayer-campaigns' ); ?></option>
                        </select>
                    </label>
                    <label>
                        <strong><?php esc_html_e( 'For how long', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        <select id="cp-individual-prayer-time-duration-select" class="cp-time-duration-select" style="margin: auto"></select>
                    </label>
                    <div>
                        <button class="button" id="cp-add-prayer-time" data-day="" disabled style="margin: 10px 0; display: inline-block"><?php esc_html_e( 'Add prayer time', 'disciple-tools-prayer-campaigns' ); ?></button>
                        <span style="display: none" id="cp-time-added"><?php esc_html_e( 'Time added', 'disciple-tools-prayer-campaigns' ); ?></span>
                    </div>
                    <p class="timezone-label">
                    <svg height='16px' width='16px' fill="#000000" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"><path d="M50,13c20.4,0,37,16.6,37,37S70.4,87,50,87c-20.4,0-37-16.6-37-37S29.6,13,50,13 M50,5C25.1,5,5,25.1,5,50s20.1,45,45,45  c24.9,0,45-20.1,45-45S74.9,5,50,5L50,5z"></path><path d="M77.9,47.8l-23.4-2.1L52.8,22c-0.1-1.5-1.3-2.6-2.8-2.6h-0.8c-1.5,0-2.8,1.2-2.8,2.7l-1.6,28.9c-0.1,1.3,0.4,2.5,1.2,3.4  c0.9,0.9,2,1.4,3.3,1.4h0.1l28.5-2.2c1.5-0.1,2.6-1.3,2.6-2.9C80.5,49.2,79.3,48,77.9,47.8z"></path></svg><a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                    </p>

                    <div style="margin: 30px 0">
                        <h3><?php esc_html_e( 'Selected Times', 'disciple-tools-prayer-campaigns' ); ?></h3>
                        <ul class="cp-display-selected-times">
                            <li><?php esc_html_e( 'No selected Time', 'disciple-tools-prayer-campaigns' ); ?></li>
                        </ul>
                    </div>

                </div>

                <div class="success-confirmation-section">
                    <div class="cell center">
                        <h2><?php esc_html_e( 'Your new prayer times have been saved.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    </div>
                </div>


                <div class="center hide-on-success">
                    <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                    </button>

                    <button disabled id="cp-confirm-individual-times" class="button submit-form-button loader">
                        <?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?>
                    </button>
                </div>
                <button class="button button-cancel clear confirm-view" id="back-to-select" aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'back', 'disciple-tools-prayer-campaigns' )?>
                </button>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="center">
                    <button class="button success-confirmation-section close-ok-success" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'ok', 'disciple-tools-prayer-campaigns' )?>
                    </button>
                </div>
            </div>


            <?php
                $notifications = isset( $post["receive_prayer_time_notifications"] ) && !empty( $post["receive_prayer_time_notifications"] );
            ?>


            <style>
                .danger-zone{
                    margin: 0 8px 0 8px;
                    display: flex;
                    justify-content: space-between;
                }

                .chevron img{
                    vertical-align: middle;
                    width: 20px;
                    cursor: pointer;
                }

                .danger-zone-content {
                    display: flex;
                    justify-content: space-between;
                    margin: 24px 10% 0 5%;
                }
                .collapsed {
                    display: none;
                }

                .toggle_up {
                    -moz-transform: scale(-1, -1);
                    -o-transform: scale(-1, -1);
                    -webkit-transform: scale(-1, -1);
                    transform: scale(-1, -1);
                }
            </style>
            <script>
                function toggle_danger() {
                    $('.danger-zone-content').toggleClass('collapsed');
                    $('.chevron').toggleClass('toggle_up');
                }
            </script>

            <div style="margin-top: 50px">
                <hr>
                <h2><?php esc_html_e( 'Profile Settings', 'disciple-tools-prayer-campaigns' ); ?></h2>
                <div><?php esc_html_e( 'Receive prayer time notifications', 'disciple-tools-prayer-campaigns' ); ?> <span class="notifications_allowed_spinner loading-spinner"></span>
                    <select name="allow_notifications" id="allow_notifications">
                        <option <?php selected( $notifications ) ?> value="allowed"><?php esc_html_e( 'Notifications allowed', 'disciple-tools-prayer-campaigns' ); ?> ✅</option>
                        <option <?php selected( !$notifications ) ?> value="disallowed"><?php esc_html_e( 'Notifications not allowed', 'disciple-tools-prayer-campaigns' ); ?> ❌</option>
                    </select>
                </div>
                <div class="danger-zone">
                    <h2><?php esc_html_e( 'Advanced Settings', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    <button class="chevron" onclick="toggle_danger();">
                        <img src="<?php echo esc_html( get_template_directory_uri() ); ?>/dt-assets/images/chevron_down.svg">
                    </button>
                </div>
                <div class="danger-zone-content collapsed">
                    <label>
                        <?php esc_html_e( 'Delete this profile and all the scheduled prayer times?', 'disciple-tools-prayer-campaigns' ); ?>
                    </label>
                    <button class="button alert" data-open="delete-profile-modal"><?php esc_html_e( 'Delete', 'disciple-tools-prayer-campaigns' ); ?></button>
                    <!-- Reveal Modal Daily time slot-->
                    <div id="delete-profile-modal" class="reveal tiny" data-reveal>
                        <h2><?php esc_html_e( 'Are you sure you want to delete your profile?', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <p>
                            <?php esc_html_e( 'This can not be undone.', 'disciple-tools-prayer-campaigns' ); ?>
                        </p>
                        <p id="delete-account-errors"></p>


                        <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                            <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                        </button>
                        <button class="button loader alert" type="button" id="confirm-delete-profile">
                            <?php echo esc_html__( 'Delete', 'disciple-tools-prayer-campaigns' )?>
                        </button>

                        <button class="close-button" data-close aria-label="Close modal" type="button">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function error_body(){
        ?>
        <div class="center" style="margin-top:50px">
            <h2 class=""><?php esc_html_e( 'This subscription has ended or is not configured correctly.', 'disciple-tools-prayer-campaigns' ); ?></h2>
        </div>
        <?php
    }

    public function add_api_routes() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'manage_profile' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type . '/delete_profile', [
                [
                    'methods'  => "DELETE",
                    'callback' => [ $this, 'delete_profile' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type . '/allow-notifications', [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'allow_notifications' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function delete_profile( WP_REST_Request $request ){
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $params["parts"]["post_id"]; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }
        global $wpdb;

        //remove connection
        $a = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts p SET post_title = 'Deleted Subscription', post_name = 'Deleted Subscription' WHERE p.ID = %s", $post_id ) );
        $a = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->dt_activity_log WHERE object_id = %s and action != 'add_subscription' and action !='delete_subscription'", $post_id ) );
        //create activity for connection removed on the campaign
        DT_Posts::update_post( 'subscriptions', $post_id, [ "campaigns" => [ "values" => [], "force_values" => true ] ], true, false );
        $a = $wpdb->query( $wpdb->prepare( "DELETE pm FROM $wpdb->postmeta pm WHERE pm.post_id = %s", $post_id ) );
        $a = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->dt_reports WHERE post_id = %s", $post_id ) );
        DT_Posts::update_post( 'subscriptions', $post_id, [ "status" => 'inactive' ], true, false );

        return true;
    }

    public function manage_profile( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        // manage
        $post_id = $params["parts"]["post_id"]; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }

        switch ( $action ) {
            case 'get':
                return $this->get_subscriptions( $post_id );
            case 'delete':
                return $this->delete_subscriptions( $post_id, $params );
            case 'add':
                return $this->add_subscriptions( $post_id, $params );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function get_subscriptions( $post_id ) {
        $subs = Disciple_Tools_Reports::get( $post_id, 'post_id' );

        if ( ! empty( $subs ) ){
            foreach ( $subs as $index => $sub ) {
                // verification step
                if ( $sub['value'] < 1 ) {
                    Disciple_Tools_Reports::update( [
                        'id' => $sub['id'],
                        'value' => 1
                    ] );
                    $subs[$index]['value'] = 1;
                    $subs[$index]['verified'] = true;
                }

                $subs[$index]['formatted_time'] = gmdate( 'F d, Y @ H:i a', $sub['time_begin'] ) . ' for ' . $sub['label'];
            }
        }

        return $subs;
    }

    private function delete_subscriptions( $post_id, $params ) {
        $sub = Disciple_Tools_Reports::get( $params["report_id"], 'id' );
        $time_in_mins = ( $sub["time_end"] - $sub["time_begin"] ) / 60;
        //@todo convert timezone?
        $label = "Commitment deleted: " . gmdate( 'F d, Y @ H:i a', $sub['time_begin'] ) . ' UTC for ' . $time_in_mins . ' minutes';
        Disciple_Tools_Reports::delete( $params['report_id'] );
        dt_activity_insert([
            'action' => 'delete_subscription',
            'object_type' => $this->post_type, // If this could be contacts/groups, that would be best
            'object_subtype' => 'report',
            'object_note' => $label,
            'object_id' => $post_id
        ] );
        return $this->get_subscriptions( $post_id );
    }

    private function add_subscriptions( $post_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( !isset( $post["campaigns"][0]["ID"] ) ){
            return false;
        }
        $campaign_id = $post["campaigns"][0]["ID"];

        foreach ( $params['selected_times'] as $time ){
            if ( !isset( $time["time"] ) ){
                continue;
            }
            $new_report = DT_Subscriptions::add_subscriber_time( $campaign_id, $post_id, $time["time"], $time["duration"], $time['grid_id'] );
            if ( !$new_report ){
                return new WP_Error( __METHOD__, "Sorry, Something went wrong", [ 'status' => 400 ] );
            }
        }
        return $this->get_subscriptions( $params['parts']['post_id'] );
    }


    public function allow_notifications( WP_REST_Request $request ){
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $params["parts"]["post_id"]; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }

        return update_post_meta( $post_id, "receive_prayer_time_notifications", !empty( $params["allowed"] ) );

    }
}
