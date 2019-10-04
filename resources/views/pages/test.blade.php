@extends('iranpayment::layouts.master')

@section('title', 'درگاه آزمایشی')

@section('content')
<div class="container">
	<div class="content">
        <h1>شناسه خرید شما</h1>
        <h1 class="content-head">{!! $transaction_code !!}</h1>
		<h1>شناسه پرداخت شما</h1>
        <h1 class="content-head">{!! $reference_number ?? '' !!}</h1>
		<h2>پرداخت با موفقیت انجام شد</h2>
		<h3><a href="{{route('iranpayment.test.verify', $transaction_code)}}">تایید پرداخت</a></h3>
	</div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
</script>
@endsection