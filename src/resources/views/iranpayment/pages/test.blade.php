@extends('iranpayment.layouts.master')

@section('title', 'درگاه آزمایشی')

@section('content')
<div class="container">
	<div class="content">
        <h1>شناسه خرید شما</h1>
        <h1 class="content-head">{!! $transaction->transaction_code !!}</h1>
		<h1>شناسه پرداخت شما</h1>
        <h1 class="content-head">{!! $transaction->reference_number !!}</h1>
		<h2>پرداخت با موفقیت انجام شد</h2>
		<h3><a href="{{url('bank', [$transaction->transaction_code, 'verify'])}}">تایید پرداخت</a></h3>
		<p>برای تایید پرداخت مسیر زیر را در فایل web.php ایجاد کنید.</p>
		<p>/bank/{transaction}/verify</p>
	</div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
</script>
@endsection