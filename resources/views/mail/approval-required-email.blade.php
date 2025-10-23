<h2>Pending Request</h2>
<div>
    <!-- I begin to speak only when I am certain what I will say is not better left unsaid. - Cato the Younger -->
    You have a request pending your approval
</div>

<h3>Event  Details: </h3>
@foreach($eventData as $key=>$value )
    <li>{{$key}}: {{$value}}</li>
@endforeach
