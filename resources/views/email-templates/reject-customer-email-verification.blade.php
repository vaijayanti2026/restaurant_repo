@extends('email-templates.layout.app')
@section('content')
<p style="text-align:center; font-weight:bold;">Email Verification Token</p>

<p> We have sent you this email in response to your request to verify your email. After you verify email, you will be able to register with your email address.</p>
        <p>To verify your email, please use the token below :</p>
        <a href="javascript:" class="button">{{$token}}</a>
        <p>If you need help, or you have any other questions, feel free to email us.</p>
        <p> From Customer Service:</p>
@endsection