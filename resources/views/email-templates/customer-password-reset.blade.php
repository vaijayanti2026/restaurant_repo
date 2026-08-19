@extends('email-templates.layout.app')
@section('content')
 <p style="font-size: 14px; line-height: 160%;margin-bottom:13px;">
     <span style="text-align:center; font-weight:900; font-size:23px">FORGOT PASSWORD</span>
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    
  
    We have sent you this email in response to your request to reset your password. After you reset your password, you will be able to login with your new password.

                            
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    To reset your password, please use the token below :
                            
</p>
<p style="font-size: 14px; line-height: 160%; margin:23px">
    <button type="button" style="padding: 10px;
    width: 130px;
    border-radius: 8px;
    background: red;
    font-size: 22px;
    border: 1px solid red;
    color: white;
    font-weight: 700;">{{$token}}</button></button>
</p>

<p style="font-size: 14px; line-height: 160%; text-align:justify;">
 We recommend that you keep your password secure and not share it with anyone.If you feel your password has been compromised, you can change it by going to your app, My Account Page and clicking on the "Change Email Address or Password" link.

</p>                           
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
 If you need help, or you have any other questions, feel free to email us.                           
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
<a href="mailto:team@catrinafreshmex.com">team@catrinafreshmex.com</a>
 </p>

 @endsection