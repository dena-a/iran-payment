@extends('iranpayment::layouts.master')

@section('title', 'انتقال به درگاه سامان (Pay.ir)')

@section('content')
<div id="loading-bar" class="loading-bar" style="width:0%;"></div>
<div class="container">
	<div class="content">
		<h1>شناسه خرید شما</h1>
		<h1 class="content-head">{!! $transaction_code !!}</h1>
		<h2>درحال انتقال به درگاه سامان (Pay.ir)...</h2>
		<div class="l-box-lrg is-center pure-u-1 pure-u-md-1-2 pure-u-lg-2-5">
			<img class="pure-img-responsive" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/pay.png">
		</div>
		<form id="bankForm" method="GET" action="{!! $bank_url !!}">
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
		}, 9000);
	};
</script>
@endsection