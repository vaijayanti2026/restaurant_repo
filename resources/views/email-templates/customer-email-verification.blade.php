@extends('email-templates.layout.app')
@section('content')
 <p style="font-size: 14px; line-height: 160%;margin-bottom:13px;">
     <span style="text-align:center; font-weight:900; font-size:23px">Email Verification Code</span>
</p>
<p style="font-size: 14px; line-height: 160%;">
    
    We have sent you this email in response to your request to verify your email. After you verify email, you will be able to register with your email address.

                            
</p>
<p style="font-size: 14px; line-height: 160%;">
   To verify your email, please use the code below :
                            
</p>
<p style="font-size: 14px; line-height: 160%; margin:23px">
    <button type="button" style="padding: 10px;
    width: 130px;
    border-radius: 8px;
    background: red;
    font-size: 22px;
    border: 1px solid red;
    color: white;
    font-weight: 700;">{{$token}}</button>
</p>
<p style="font-size: 14px; line-height: 160%;">
 If you need help, or you have any other questions, feel free to email us.                           
</p>
<p style="font-size: 14px; line-height: 160%;">
From Customer Service
 </p>

 @endsection