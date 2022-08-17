@extends('iranpayment::layouts.master')

@php
	if ($status === '') {
		$title = "درحال انتقال به درگاه {$title}...";
	} else {
		$title = "درحال انتقال به درگاه پرداخت...";
	}
@endphp

@section('title', $title)

@section('content')
<div class="container">
	<div class="content">
        <h1>{{ $title }}</h1>

        <h2>شناسه خرید شما</h2>
        <h2 class="content-head">{{ $transaction_code }}</h2>
		@if (isset($image))
			<div class="l-box-lrg is-center pure-u-1 pure-u-md-1-2 pure-u-lg-2-5">
				<img class="pure-img-responsive" src="{{ $image }}">
			</div>
		@endif

        <a class="pure-button pure-button-default" href="{{ $button_url }}">{{ $button_text }}</a>
	</div>
</div>
@endsection
