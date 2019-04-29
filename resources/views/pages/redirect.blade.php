@extends('iranpayment::layouts.master')

@php
	if (isset($title)) {
		$title = "درحال انتقال به درگاه $title...");
	} else {
		$title = "درحال انتقال به درگاه پرداخت...");
	}
@endphp

@section('title', $title)

@section('content')
<div id="loading-bar" class="loading-bar" style="width:0%;"></div>
<div class="container">
	<div class="content">
		<h1>شناسه خرید شما</h1>
		<h1 class="content-head">{!! $transaction_code !!}</h1>
		<h2>{{ $title }}</h2>
		@if (isset($image) && file_exists($image))
			<div class="l-box-lrg is-center pure-u-1 pure-u-md-1-2 pure-u-lg-2-5">
				<img class="pure-img-responsive" src="{{ $image }}">
			</div>
		@endif
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