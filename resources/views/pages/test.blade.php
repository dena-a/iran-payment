@extends('iranpayment::layouts.master')

@section('title', 'درگاه آزمایشی')

@section('content')
<div class="container">
	<div class="content">
        <h2>شناسه خرید شما</h2>
        <h2 class="content-head">{{ $transaction_code }}</h2>

        <h1>وضعیت مورد نظر را انتخاب کنید:</h1>
        <form method="POST" action="{{ $callback_url }}" class="pure-form pure-form-aligned">
            <fieldset>
                <div class="pure-control-group">
                    <button class="pure-button button-success" type="submit" name="status" value="success">پرداخت موفق</button>
                </div>
                <div class="pure-control-group">
                    <button class="pure-button button-error" type="submit" name="status" value="error">پرداخت ناموفق</button>
                </div>
            </fieldset>
        </form>
	</div>
</div>
@endsection
