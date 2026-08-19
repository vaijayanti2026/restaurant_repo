@extends('email-templates.layout.app')
@section('content')
 <p style="font-size: 14px; line-height: 160%;margin-bottom:13px;">
     <span style="text-align:center; font-weight:900; font-size:23px">Welcome to Catrina Fresh Mex! </span>
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    
  
  Hi {{$user->f_name.' '.$user->l_name}}

                            
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    
  
  Welcome to the Catrina Fresh Mex family! 

                            
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    
We’re thrilled to have you join us on this flavorful journey. Get ready to experience the freshest, most authentic Mexican cuisine deliver.

                            
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
    
Click the <b>button below</b> to explore our menu and satisfy your cravings with just a few clicks!

                            
</p>
<p style="font-size: 14px; line-height: 160%; margin:23px">
    <a href="https://order.catrinafreshmex.com/" style="padding: 10px;
    width: 130px;
    border-radius: 8px;
    background: red;
    font-size: 22px;
    border: 1px solid red;
    color: white;
    text-decoration: none;
    font-weight: 700;">Order Now</a>
</p>


                        
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
 If you need help, or you have any other questions, feel free to email us.                           
</p>
<p style="font-size: 14px; line-height: 160%; text-align:justify;">
<a href="mailto:team@catrinafreshmex.com">team@catrinafreshmex.com</a>
 </p>

 @endsection