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
            <td><b>Number of Invitation Accepted:</b>&nbsp;<?php echo $acceptedPeopelCount?></td>
        </tr>
        <tr>
            <td><b>List Name:</b>&nbsp;<?php echo $data['list_name']. '( '.$listCount.' )'?></td>
        </tr>
        <tr>
            <td><b>List of people who accept the invitation:</b></td>
        </tr>
    </tbody>
</table>

<table width="400" >
    <tbody >
        <tr >
            <th align="left"><b>Name</b></th>
            <th align="left"><b>Phone</b></th>
        </tr>
            <?php
                $contactList = $data['contact_list']['contact_list'];
                $list = json_decode($contactList); ?>
                @foreach ($list as $value)
                    <tr>
                        @if(isset($value->name))
                            @if($value->name !== '')
                                <td> <?php echo $value->name ?> </td>
                             @else
                                <td> </td>
                            @endif
                        @else
                            <td> <?php echo $value->email ?> </td>
                        @endif
                            <td> <?php echo $value->phone ?> </td>
                    </tr>
                @endforeach
    </tbody>
</table>
</html>