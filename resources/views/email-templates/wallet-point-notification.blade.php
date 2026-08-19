@extends('email-templates.layout.app')
@section('content')
<p>
    <img src="{{asset('public/assets/admin/img/rewardpointsemail.png')}}" style="width:121px"/>
</p>
 <p style="font-size: 14px; line-height: 160%;margin-bottom:13px;">
     <span style="text-align:center; font-weight:900; font-size:23px">Reward Points</span>
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
  Hi {{$username}}                        
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    Thank you for your recent purchase at Catrina Fresh Mex! We're excited to let you know that we've added <b>{{ $reward }}</b> reward points to your account as a token of our appreciation.
                            
</p>

<p style="font-size: 14px; line-height: 160%; margin:23px">
    <button type="button" style="padding: 8px;
    width: 178px;
    border-radius: 8px;
    background: red;
    font-size: 20px;
    border: 1px solid red;
    color: white;
    font-weight: 400;">{{$points}} Reward Points</button></button>
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    You can use these points on your next order to enjoy discounts or special offers. We love rewarding our loyal customers, and we can't wait to serve you again soon!
</p>

                           
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
 If you need help, or you have any other questions, feel free to email us.                           
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
<a href="mailto:team@catrinafreshmex.com">team@catrinafreshmex.com</a>
 </p>

 @endsection