@extends('iranpayment.layouts.master')

@section('title', 'انتقال به درگاه بانک سامان')

@section('content')
<div id="loading-bar" class="loading-bar" style="width:0%;"></div>
<div class="container">
	<div class="content">
		<h1>شناسه خرید شما</h1>
		<h1 class="content-head">{!! $‫‪res_number‬‬ !!}</h1>
		<h2>درحال انتقال به درگاه بانک سامان...</h2>
		<div class="l-box-lrg is-center pure-u-1 pure-u-md-1-2 pure-u-lg-2-5">
			<img class="pure-img-responsive" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/src/resources/assets/img/sep.png">
		</div>
		<form id="bankForm" method="POST" action="{!! $bank_url !!}">
			<input type="hidden" name="Token" value="{!! $token !!}">';
			<input type="hidden" name="RedirectURL" value="{!! $redirect_url !!}">';
			<button type="submit" class="pure-button pure-button-default">انتقال به صفحه پرداخت</button>
		</form>
	</div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
	setInterval(function (x) {
		var width = document.getElementById('loading-bar').style.width;
		if (parseInt(width) < 100) {
			document.getElementById('loading-bar').style.width = (0.05 + parseFloat(width))+'%';
		}
	}, 5);
	window.onload=function(){ 
		window.setTimeout(function() {
			document.getElementById('bankForm').submit();
		}, 10000);
	};
</script>
@endsection