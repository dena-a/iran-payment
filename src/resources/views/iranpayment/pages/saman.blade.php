@extends('iranpayment.layouts.master')

@section('title', 'انتقال به درگاه بانک سامان')

@section('content')
<form id="bankForm" method="POST" action="{!! $bank_url !!}">
	<input type="hidden" name="Token" value="{!! $token !!}">';
	<input type="hidden" name="RedirectURL" value="{!! $redirect_url !!}">';
</form>
@endsection

@section('javascript')
<script type="text/javascript">
	window.onload=function(){ 
		window.setTimeout(function() {
			document.getElementById('bankForm').submit();
		}, 5000);
	};
</script>
@endsection