<!DOCTYPE html>
<html lang="fa">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>@yield('title')</title>
	@include('iranpayment::styles.pure')
	@include('iranpayment::styles.style')
</head>
<body>
	@yield('content')
	@yield('javascript')
	@include('iranpayment::sections.footer')
</body>
</html>