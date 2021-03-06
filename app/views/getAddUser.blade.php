@extends('layouts.master')

@section('content')
     <div class="row">
        <div class="col-md-6 col-md-offset-3 modal-outer noPad">
	        <h1>Add New User</h1>
	        @if(Session::has('errors'))
	        	<ul>
	        	@foreach(Session::get('errors') as $errors)
	        		@foreach($errors as $error)
	        			<li>{{{$error}}}</li>
	        		@endforeach
	        	@endforeach
	        	</ul>
	        @endif
	        <form class="form-horizontal" role="form" action="" method="POST">
			  <div class="form-group">
			    <label for="username" class="col-sm-4 control-label">Username</label>
			    <div class="col-sm-6">
			      <input type="text" class="form-control" name="username" placeholder="Username" required>
			    </div>
			  </div>
			  <div class="form-group">
			    <label for="Full Name" class="col-sm-4 control-label">Full Name</label>
			    <div class="col-sm-6">
			      <input type="text" class="form-control" name="name" placeholder="Name" required>
			    </div>
			  </div>
			  <div class="form-group">
			    <label for="password" class="col-sm-4 control-label">Password</label>
			    <div class="col-sm-6">
			      <input type="password" class="form-control" name="password" placeholder="Password" required>
			    </div>
			  </div>
			  <div class="form-group">
			    <label for="password_confirmation" class="col-sm-4 control-label">Confirm Password</label>
			    <div class="col-sm-6">
			      <input type="password" class="form-control" name="password_confirmation" placeholder="New Password" required>
			    </div>
			  </div>
			  <div class="form-group">
			    <label for="admin" class="col-sm-4 control-label">Role</label>
			    <div class="col-sm-6">
			    <label class="radio-inline">
			      <input type="radio" name="admin" value="0" checked>Standard User
			    </label>
			    <label class="radio-inline">
			      <input type="radio" name="admin" value="1" >Admin User
			    </label>
			    </div>
			  </div>
			  <input type="hidden" name="_token" value="{{{csrf_token()}}}">
			  <div class="form-group">
			    <div class="col-sm-offset-4 col-sm-6">
			      <button type="submit" class="btn btn-default">Add User</button>
			    </div>
			  </div>
			</form>
			<h4>Notes:</h4>
			<ul>
			<li>All will be asked to enroll with duosecurity. If the user already uses it in your organisation, keep the username same as the one they already use with duo security.</li>
			<li>Admin Users can add/remove other users (including other admin users)</li>
			</ul>
	     </div>
	</div>
@stop