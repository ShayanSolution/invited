@php
    if (!is_null($data['event_only_time'])){
        $formatTime = 'h:i A';
        $time = $data['event_only_time'];
        $onlyTime = Carbon\Carbon::parse($time)->format($formatTime);
    }else{
        $onlyTime = "";
    }
    $input  = $data['event_time'];
    $input2  = $data['created_at'];
    $format1 = 'Y-m-d';
    $format2 = 'h:i A';
    $date = Carbon\Carbon::parse($input)->format($format1);
    $date2 = Carbon\Carbon::parse($input2)->format($format1);
    $time2 = Carbon\Carbon::parse($input2)->format($format2);
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <h1 style="color:#1b6d85"><center><u>Event Report</u></center></h1>
</head>

<table style="font-family: Arial">
    <tbody>

    <tr>
        <td><b>Event Type:</b>&nbsp;Send By Me</td>
    </tr>
    <tr>
        <td><b>Event Title:</b>&nbsp;<?php echo $data['title']?></td>
    </tr>
    <tr>
        <td><b>Event Location:</b>&nbsp;<?php echo $data['event_address']?></td>
    </tr>
    <tr>
        <td><b>Event Sent Date:</b>&nbsp;<?php echo $date2?></td>
    </tr>
    <tr>
        <td><b>Event Sent Time:</b>&nbsp;<?php echo $time2?></td>
    </tr>
    <tr>
        <td><b>Event Date:</b>&nbsp;<?php echo $data['event_date']?></td>
    </tr>
    <tr>
        <td><b>Event Time:</b>&nbsp;<?php echo $onlyTime ?></td>
    </tr>
    <tr>
        <td><b>List Name:</b>&nbsp;<?php echo $data['list_name']. ' ('.$listCount.')'?></td>
    </tr>
    <tr>
        <td><b>Yes Count:</b>&nbsp;<?php echo $acceptedPeopelCount?></td>
    </tr>
    <tr>
        <td><b>No Count:</b>&nbsp;<?php echo $rejectPeopelCount?></td>
    </tr>
    <tr>
        <td><b>No Response Count:</b>&nbsp;<?php echo $pendingPeopelCount?></td>
    </tr>
    <tr>
        <td> </td><td><b><u>List Of People</u></b></td>
    </tr>
    </tbody>
</table>
<br>
@if(!empty($acceptFilteredContacts))
<table width="400" >
    <tbody >
        <tr >
            <td><b>List of people who replied with yes:</b></td>
        </tr>
        <tr >
            <th align="left"><b>Name</b></th>
            <th align="left"><b>Phone</b></th>
        </tr>

        @foreach($acceptFilteredContacts as $contact)
            <tr>
                <td> {!! $contact['name'] or '' !!} </td>
                <td> {!! $contact['phone'] !!} </td>
            </tr>
        @endforeach
    </tbody>
</table>
<p>======================================================</p>
@endif
@if(!empty($rejectFilteredContacts))
<table width="400" >
    <tbody >
        <tr >
            <td><b>List of people who replied with no:</b></td>
        </tr>
        <tr >
            <th align="left"><b>Name</b></th>
            <th align="left"><b>Phone</b></th>
        </tr>

        @foreach($rejectFilteredContacts as $contact)
            <tr>
                <td> {!! $contact['name'] or '' !!} </td>
                <td> {!! $contact['phone'] !!} </td>
            </tr>
        @endforeach
    </tbody>
</table>
<p>======================================================</p>
@endif
@if(!empty($pendingFilteredContacts))
<table width="400" >
    <tbody >
        <tr >
            <td><b>List of people with no response:</b></td>
        </tr>
        <tr >
            <th align="left"><b>Name</b></th>
            <th align="left"><b>Phone</b></th>
        </tr>

        @foreach($pendingFilteredContacts as $contact)
            <tr>
                <td> {!! $contact['name'] or '' !!} </td>
                <td> {!! $contact['phone'] !!} </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
</html>