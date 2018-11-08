@php
    $input  = $data['event_time'];
    $input2  = $data['created_at'];
    $format1 = 'Y-m-d';
    $format2 = 'H:i:s';
    $date = Carbon\Carbon::parse($input)->format($format1);
    $time = Carbon\Carbon::parse($input)->format($format2);
    $date2 = Carbon\Carbon::parse($input2)->format($format1);
    $time2 = Carbon\Carbon::parse($input2)->format($format2);
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <h1><center>Event Report</center></h1>
</head>

<table>
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
        <td><b>Event Date:</b>&nbsp;<?php echo $date?></td>
    </tr>
    <tr>
        <td><b>Event Time:</b>&nbsp;<?php echo $time ?></td>
    </tr>
    <tr>
        <td><b>Number of Invitation Accepted:</b>&nbsp;<?php echo $data['max_invited']?></td>
    </tr>
    <tr>
        <td><b>List Name:</b>&nbsp;<?php echo $data['list_name']?></td>
    </tr>
    <tr>
        <td><b>List of people who accept the invitation:</b></td>
    </tr>
    <tr>
        <td><b>Name</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Phone</b></td>
    </tr>
    <tr>
        <td>
        <?php
            $contactList = $data['contact_list']['contact_list'];
            $list = json_decode($contactList); ?>
            @foreach ($list as $value)
                @if(isset($value->name))
                <?php echo $value->name ?>
                @else
                    <?php echo $value->email ?>
                @endif
                    &nbsp;&nbsp;&nbsp;&nbsp;
                <?php echo $value->phone ?>
                <br>
            @endforeach
        </td>
    </tr>

    </tbody>
</table>
</html>