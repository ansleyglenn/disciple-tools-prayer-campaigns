"use strict";

let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
let selected_times = [];

let calendar_subscribe_object = {
  coverage_percentage: 0,
  second_level: 0,
  start_timestamp: 0,
  end_timestamp: 0,
  slot_length: 15,
  duration_options: {}
}
let escapeObject = (obj) => {
  return Object.fromEntries(Object.entries(obj).map(([key, value]) => {
    return [ key, window.lodash.escape(value)]
  }))
}

jQuery(document).ready(function($) {
  let jsObject = window.campaign_objects

  let link =  jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/campaign_info';
  if ( window.campaign_objects.remote){
    link =  jsObject.root + jsObject.parts.root + '/v1/24hour-router';
  }
  jQuery.ajax({
    type: "GET",
    data: {action: 'get', parts: jsObject.parts, 'url': 'campaign_info', time: new Date().getTime() },
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: link
  })
  .done(function (data) {

    calendar_subscribe_object = { ...calendar_subscribe_object, ...data}
    calendar_subscribe_object.translations = escapeObject(jsObject.translations)
    $('#cp-wrapper').removeClass("loading-content")

    $('.cp-loading-page').hide()
    if (  calendar_subscribe_object.status === "inactive"){
      $('#cp-view-closed').show()
      $("#cp-wrapper").css("min-height", '500px')
    } else {
      $('#cp-main-page').show()
    }
    const number_of_days = ( calendar_subscribe_object.end_timestamp + 1  - calendar_subscribe_object.start_timestamp ) / day_in_seconds
    calendar_subscribe_object.end_timestamp -= 1;
    let days = window.campaign_scripts.calculate_day_times(current_time_zone)

    //main progress circle
    $('#main-progress').html(`
      <progress-ring stroke="10" radius="80" font="18"
                     progress="${calendar_subscribe_object.coverage_percentage}"
                     progress2="${calendar_subscribe_object.second_level}"
                     text="${calendar_subscribe_object.coverage_percentage}%"
                     text2="${calendar_subscribe_object.text2 || ''}">
      </progress-ring>
    `)
    //draw progress circles
    window.customElements.define('progress-ring', ProgressRing);

    //navigation function
    $('.cp-nav').on( 'click', function (){
      $('.cp-view').hide()
      let view_to_open = $(this).data('open')
      $(`#${view_to_open}`).show()

      //force the screen to scroll to the top of the wrapper
      if ( $(this).data('force-scroll')){
        let elmnt = document.getElementById("cp-wrapper");
        elmnt.scrollIntoView();
      }

      //configure the view to go back to
      let back_to = $(this).data('back-to');
      if ( back_to ) {
        $(`#${view_to_open} .cp-close-button`).data('open', back_to)
      }
    })

    let time_committed_display = $('#cp-time-committed-display')
    time_committed_display.html(time_committed_display.text().replace( '%', calendar_subscribe_object.time_committed ));

    // let set_campaign_date_range_title = function (){
    //   let start_time = window.campaign_scripts.timestamp_to_format( calendar_subscribe_object.start_timestamp, { month: "long", day: "numeric", hour:"numeric", minute:"numeric" }, current_time_zone)
    //   let end_time = window.campaign_scripts.timestamp_to_format( calendar_subscribe_object.end_timestamp, { month: "long", day: "numeric", hour:"numeric", minute:"numeric" }, current_time_zone)
    //   let start_end = window.lodash.escape(calendar_subscribe_object.translations.campaign_duration).replace('%1$s', `<strong>${start_time}</strong>`).replace(`%2$s`,`<strong>${end_time}</strong>`)
    //   $('#cp-start-end').html(start_end);
    // }
    // set_campaign_date_range_title()

    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')
    let headers = `
      <div class="day-cell week-day">${week_day_names[0]}</div>
      <div class="day-cell week-day">${week_day_names[1]}</div>
      <div class="day-cell week-day">${week_day_names[2]}</div>
      <div class="day-cell week-day">${week_day_names[3]}</div>
      <div class="day-cell week-day">${week_day_names[4]}</div>
      <div class="day-cell week-day">${week_day_names[5]}</div>
      <div class="day-cell week-day">${week_day_names[6]}</div>
    `

    //display main calendar
    let draw_calendar = ( id = 'calendar-content') => {
      let content = $(`#${window.lodash.escape(id)}`)
      content.empty()
      let last_month = "";
      let list = ``
      days.forEach(day=>{
        if ( day.month !== last_month ){
          if ( last_month ){
            //add extra days at the month end
            let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
            if ( day_number !== 0 ){
              for ( let i = 1; i <= 7-day_number; i++ ){
                list +=  `<div class="day-cell disabled-calendar-day"></div>`
              }
            }
            list += `</div>`
          }

          list += `<h3 class="month-title"><b>${window.lodash.escape(day.month).substring(0,3)}</b> ${new Date(day.key * 1000).getFullYear()}</h3><div class="calendar">`
          if( !last_month ){
            list += headers
          }

          //add extra days at the month start
          let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
          let start_of_week = window.campaign_scripts.start_of_week(day.key, current_time_zone);
          for ( let i = 0; i < day_number; i++ ){
            list +=  `<div class="day-cell disabled-calendar-day"></div>`
          }
          last_month = day.month
        }
        if ( day.disabled ){
          list += `<div class="day-cell disabled-calendar-day">
          </div>`
        } else {
          list +=`
            <div class="display-day-cell" data-day=${window.lodash.escape(day.key)}>
                <progress-ring stroke="3" radius="20" progress="${window.lodash.escape(day.percent)}" text="${window.lodash.escape(day.day)}"></progress-ring>
            </div>
          `
        }
      })
      list += `</div>`

      content.html(`<div class="" id="selection-grid-wrapper">${list}</div>`)

      if ( window.campaign_scripts.will_have_daylight_savings( current_time_zone, calendar_subscribe_object.start_timestamp, calendar_subscribe_object.end_timestamp) ){
        $('.cp-daylight-savings-notice').show()
      }
    }
    draw_calendar()

    /**
     * daily prayer time screen
     */
    let daily_time_select = $('#cp-daily-time-select')


    let populate_daily_select = function (){
      let select_html = `<option value="false">${calendar_subscribe_object.translations.select_a_time}</option>`

      let time_index = 0;
      let start_of_today = new Date('2023-01-01')
      start_of_today.setHours(0,0,0,0)
      let start_time_stamp = start_of_today.getTime()/1000
      while ( time_index < day_in_seconds ){
        let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+time_index)
        let text = ''
        let fully_covered = window.campaign_scripts.time_slot_coverage[time_formatted] ? window.campaign_scripts.time_slot_coverage[time_formatted].length === window.campaign_scripts.time_label_counts[time_formatted] : false;
        let level_covered =  window.campaign_scripts.time_slot_coverage[time_formatted] ? Math.min(...window.campaign_scripts.time_slot_coverage[time_formatted]) : 0
        if ( fully_covered && level_covered > 1  ){
          text = `(${calendar_subscribe_object.translations.fully_covered_x_times.replace( '%1$s', level_covered)})`
        } else if ( fully_covered ) {
          text = `(${calendar_subscribe_object.translations.fully_covered_once})`
        } else if ( window.campaign_scripts.time_slot_coverage[time_formatted] ){
          text = `${calendar_subscribe_object.translations.percent_covered.replace('%s', (window.campaign_scripts.time_slot_coverage[time_formatted].length / number_of_days * 100).toFixed(1) + '%')}`
        }
        select_html += `<option value="${window.lodash.escape(time_index)}">
          ${window.lodash.escape(time_formatted)} ${ window.lodash.escape(text) }
        </option>`
        time_index += calendar_subscribe_object.slot_length * 60
      }
      daily_time_select.empty();
      daily_time_select.html(select_html)
    }
    populate_daily_select()

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
        if ( !already_added && time > now && time >= calendar_subscribe_object['start_timestamp'] && time < calendar_subscribe_object['end_timestamp'] ){
          selected_times.push({time: time, duration: duration, label: time_label})
        }
      }
      display_selected_times();
    })

    let display_missing_time_slots = function (){
      let ordered_missing = [];
      Object.keys(window.campaign_scripts.missing_slots).forEach(k=>{
        ordered_missing.push({'label':k, slots:window.campaign_scripts.missing_slots[k]})
      })

      ordered_missing.sort((a,b)=>a.slots.length-b.slots.length)
      if ( ordered_missing.length > 0 ){
        $('#cp-missing-times-container').show()
      }

      let content = ``;
      let index = 0;
      ordered_missing.forEach(m=>{
        index++;
        content += `<div class="missing-time-slot" style="${index>5?'display:none':''}"><strong>${m.label}:</strong>&nbsp;`
        if ( m.slots.length < 5 ){
          content += m.slots.slice(0, 5).map(a=>{return window.campaign_scripts.timestamp_to_month_day(a)}).join(', ')
        } else {
          content += calendar_subscribe_object.translations.on_x_days.replace('%s', m.slots.length)
        }
        content += `.<button class="cp-select-missing-time clear-button" value="${m.label}" style="padding:5px">${calendar_subscribe_object.translations.pray_this_time}</button>`
        content += `</div>`
      })
      if( ordered_missing.length >= 5 ){
        content += `<div class="missing-time-slot">
          <button class="clear-button" style="border: none; padding: 2px; background-color: transparent;" id="cp-show-more-missing">
            <strong>${calendar_subscribe_object.translations.and_x_more.replace('%s', ordered_missing.length - 5)}</strong>
          </button>
        </div>`
      }

      $('#cp-missing-time-slots').html(content)
    }
    display_missing_time_slots()

    $(document).on('click', '#cp-show-more-missing', function (){
      $('.missing-time-slot').show();
      $('#cp-show-more-missing').hide();
    })

    $(document).on('click', '.cp-select-missing-time', function (){
      let label = $(this).val();
      let times = window.campaign_scripts.missing_slots[label];
      times.forEach(time=>{
        let time_label = window.campaign_scripts.timestamp_to_format( time, { month: "long", day: "numeric", hour:"numeric", minute: "numeric" }, current_time_zone)
        let already_added = selected_times.find(k=>k.time===time)
        if ( !already_added && time > now && time >= calendar_subscribe_object['start_timestamp'] && time < calendar_subscribe_object['end_timestamp'] ){
          selected_times.push({time: time, duration: calendar_subscribe_object.slot_length, label: time_label})
        }
      })
      display_selected_times();

      $('.cp-view').hide()
      let view_to_open = 'cp-view-confirm'
      $(`#${view_to_open}`).show()
      let elmnt = document.getElementById("cp-wrapper");
      elmnt.scrollIntoView();
    })


    /**
     * Individual prayer times screen
     */
    let current_time_selected = $("#cp-individual-time-select").val();

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
      if ( selected_times.length === 0 ){
         $('#cp-confirm-individual-times').attr('disabled', true)
         // $('#cp-submit-form').attr('disabled', true)
      }
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
      days.forEach(day => {
        if (day.month!==last_month) {
          if (last_month) {
            //add extra days at the month end
            let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
            if ( day_number !== 0 ) {
              for (let i = 1; i <= 7 - day_number; i++) {
                list += `<div class="day-cell disabled-calendar-day"></div>`
              }
            }
            list += `</div>`
          }

          list += `<h3 class="month-title"><b>${window.lodash.escape(day.month).substring(0,3)}</b> ${new Date(day.key * 1000).getFullYear()}</h3><div class="calendar" style="margin-bottom:20px;">`
          if (!last_month) {
            list += headers
          }

          //add extra days at the month start
          let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
          let start_of_week = window.campaign_scripts.start_of_week(day.key, current_time_zone);
          for (let i = 0; i < day_number; i++) {
            list += `<div class="day-cell disabled-calendar-day"></div>`
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
          text = `(${calendar_subscribe_object.translations.covered_once})`;
        }
        if ( slot.subscribers > 1 ) {
          text = `(${calendar_subscribe_object.translations.covered_x_times.replace( '%1$s', slot.subscribers)})`
        }
        let disabled = slot.key < calendar_subscribe_object.start_timestamp ? 'disabled' : '';
        let selected = ( slot.key % day_in_seconds) === ( current_time_selected % day_in_seconds ) ? "selected" : '';
        select_html += `<option value="${window.lodash.escape(slot.key)}" ${selected} ${disabled}>
            ${window.lodash.escape(slot.formatted)} ${window.lodash.escape(text)}
        </option>`
      })
      $('#cp-individual-time-select').html(select_html).attr('disabled', false)
    })

    $('#confirm-timezone').on('click', function (){
      current_time_zone = $("#timezone-select").val()
      update_timezone()
      days = window.campaign_scripts.calculate_day_times(current_time_zone)
      // set_campaign_date_range_title()
      populate_daily_select()
      draw_calendar()
      draw_modal_calendar()
      display_missing_time_slots()
    })

    let update_timezone = function (){
      $('.timezone-current').html(current_time_zone)
      $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
    }
    update_timezone()




  })
})
