@extends('email-templates.layout.app')
@section('content')
 <p style="font-size: 14px; line-height: 160%;margin-bottom:13px;">
     <span style="text-align:center; font-weight:900; font-size:23px">Woohoo! your order is confirmed.</span>
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    
  Thank you for choosing Catrina Fresh Mex! We appreciate your order and are excited to prepare your meal.
                            
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify; margin-bottom:42px">
   Your order ID is <b>{{$order->id}}</b>. We hope you enjoy every bite and look forward to serving you again soon.
                            
</p>
@include('email-templates.invoice')
                          
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
 If you need help, or you have any other questions, feel free to email us.                           
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    <a href="mailto:team@catrinafreshmex.com">team@catrinafreshmex.com</a>
 </p>

 @endsection