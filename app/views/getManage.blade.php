@extends('layouts.master')

@section('content')
	<div class="row">
        <div class="col-md-6 col-md-offset-3 modal-outer noPad">
        	<h2>Security Group Details</h2>
        	<div class="row">
			  <div class="col-md-3">Name:</div>
			  <div class="col-md-6">{{{$security_group['GroupName']}}}</div>
			</div>
			<div class="row">
			  <div class="col-md-3">Id:</div>
			  <div class="col-md-6">{{{$security_group['GroupId']}}}</div>
			</div>	
			<div class="row">
			  <div class="col-md-3">Description:</div>
			  <div class="col-md-6">{{{$security_group['Description']}}}</div>
			</div>	
			<div class="row">
			  <div class="col-md-3">VPC-Id:</div>
			  <div class="col-md-6">{{{$security_group['VpcId']}}}</div>
			</div>	
			<div class="row">
			  <div class="col-md-3">Name Tag: </div>
			  <div class="col-md-6">
			  @if(isset($security_group['Tags']['0']['Value']))
				{{{$security_group['Tags']['0']['Value']}}}
			  @endif
			  </div>
			</div>	
			<h2>Active Leases:</h2>
			<table class="table table-hover table-bordered">
	        <thead>
	          <tr>
	            <th>Creator</th>
	            <th>Leased IP</th>
	            <th>Protocol</th>
	            <th>Port(s)</th>
	            <th>Time Left</th>
	            <th>Terminate?</th>
	          </tr>
	        </thead>
	        <tbody>
	        	@foreach($leases as $lease)
	        	<tr>
	        		<td>{{{$lease->user->username}}}</td>
	        		<td>{{{$lease->lease_ip}}}</td>
	        		<td>{{{$lease->protocol}}}</td>
	        		<td>{{{$lease->port_from}}}-{{{$lease->port_to}}}</td>
	        		<td>
	        		<?php
	        		    //Calculating Time to Expiry in Hours and minutes
	        			$time_left=strtotime($lease->created_at)+$lease->expiry-time(); 
    					$hours=intval(floor($time_left/3600)); 
    					$minutes=intval(floor(($time_left-$hours*3600)/60));
    					echo "$hours hours $minutes minutes";
    				?>
    				</td>
    				<td><form method="post" action="">
    				<input type="hidden" name="lease_id" value="{{{$lease->id}}}" />
    				<input type="hidden" name="_token" value="{{{csrf_token()}}}">
    				<a href="" style="color: #ff0000;" onclick="if(confirm('Are you sure you want to terminate this lease?')) {parentNode.submit();} return false;">
    					<span title="Terminate Lease" class="glyphicon glyphicon-minus-sign"></span>
    				</a>
    				</form>
    				</td>	
	        	</tr>
	        	@endforeach
	        	@if(!$leases->count())
		       	<tr><td colspan="6" style="text-align:center">No Active Leases</td></tr>
		       	@endif
	        </tbody>
	        </table>
	        
	        <h2>Create Access On this Group</h2>
		    <div>
		    <button type="button" class="btn btn-primary" onclick="javascript: $('#ssh_form').show();">SSH</button>
		    <button type="button" class="btn btn-primary" onclick="javascript: $('#https_form').show();">HTTPS</button>
		    <button type="button" class="btn btn-primary" onclick="javascript: $('#custom_form').show();">Custom</button>
		    </div>
		    <br/>


			<form id="ssh_form" class="form-horizontal" role="form" style="display:none" action="" method="POST">
			  <h4>Get SSH Access:</h4>
			  <input type="hidden" name="rule_type" value="ssh" />
			  <input type="hidden" name="_token" value="{{{csrf_token()}}}" />
			  <div class="row">
			  	<label for="access" class="col-sm-4 control-label">Access For:</label>
			    <div class="col-sm-8">
				    <input type="radio" name="access" onChange="if(this.checked) $('#ssh_email').hide();" checked="checked" value="1"  /> Self
				    <input type="radio" name="access" onChange="if(this.checked) $('#ssh_email').show();" value="2"  /> Invite By Email
				    <input type="radio" name="access" onChange="if(this.checked) $('#ssh_email').hide();" value="3"  /> Invite By Link
				</div>
			  </div>
			  <div id="ssh_email" class="row" style="display:none">
			  	<label for="email" class="col-sm-4 control-label">Email:</label>
			    <div class="col-sm-8">
				    <input type="email" class="form-control" name="email" placeholder="some@someone.com"  /> 
				</div>
			  </div>
			  <div class="row">
			    <label for="expiry" class="col-sm-4 control-label">Expiry:</label>
			    <div class="col-sm-4">
				    <select name="expiry" class="form-control" required>
					  <option value="3600" selected>1 hour</option>
					  <option value="14400">4 hours</option>
					  <option value="43200">12 hours</option>
					  <option value="86400">1 Day</option>
					</select>
				</div>
				<div class="col-sm-4">
			  		<button type="submit" class="btn btn-default">Get Access</button>
			  	</div>
			  </div>
			</form>

		    <form id="https_form" class="form-horizontal" role="form" style="display:none"  action="" method="POST">
		    	<h4>Get HTTPS Access:</h4>
			    <input type="hidden" name="rule_type" value="https" />
			    <input type="hidden" name="_token" value="{{{csrf_token()}}}">
			    <div class="row">
				  	<label for="access" class="col-sm-4 control-label">Access For:</label>
				    <div class="col-sm-8">
					    <input type="radio" name="access" onChange="if(this.checked) $('#https_email').hide();" checked="checked" value="1"  /> Self
					    <input type="radio" name="access" onChange="if(this.checked) $('#https_email').show();" value="2"  /> Invite By Email
					    <input type="radio" name="access" onChange="if(this.checked) $('#https_email').hide();" value="3"  /> Invite By Link
					</div>
				</div>
				<div id="https_email" class="row" style="display:none">
				  	<label for="email" class="col-sm-4 control-label">Email:</label>
				    <div class="col-sm-8">
					    <input type="email" class="form-control" name="email" placeholder="some@someone.com"  /> 
					</div>
			    </div>
			    <div class="row">
			    	<label for="expiry" class="col-sm-4 control-label">Expiry:</label>
			    	<div class="col-sm-4">
			    		<select name="expiry" class="form-control" required>
						  <option value="3600" selected>1 hour</option>
						  <option value="14400">4 hours</option>
						  <option value="43200">12 hours</option>
						  <option value="86400">1 Day</option>
						</select>
					</div>
			    <div class="col-sm-4">
			  		<button type="submit" class="btn btn-default">Get Access</button>
			  	</div>
			  	</div>

		    </form>
		    
		    <form id="custom_form" class="form-horizontal" role="form-horizontal" style="display:none" action="" method="POST">
			    <h4>Define Custom Rule:</h4>
			    <input type="hidden" name="rule_type" value="custom" />
			    <input type="hidden" name="_token" value="{{{csrf_token()}}}">
			    <div class="row">
				  	<label for="access" class="col-sm-4 control-label">Access For:</label>
				    <div class="col-sm-8">
					    <input type="radio" name="access" onChange="if(this.checked) $('#custom_email').hide();" checked="checked" value="1"  /> Self
					    <input type="radio" name="access" onChange="if(this.checked) $('#custom_email').show();" value="2"  /> Invite By Email
					    <input type="radio" name="access" onChange="if(this.checked) $('#custom_email').hide();" value="3"  /> Invite By Link
					</div>
				</div>
				<div id="custom_email" class="row" style="display:none">
				  	<label for="email" class="col-sm-4 control-label">Email:</label>
				    <div class="col-sm-8">
					    <input type="email" class="form-control" name="email" placeholder="some@someone.com"  /> 
					</div>
			    </div>
			    <div class="row">
			    	<label for="protocol" class="col-sm-4 control-label">Protocol</label>
			    	<div class="col-sm-8">
			    		<input type="text" name="protocol" placeholder="tcp/udp" class="form-control" required />
			    	</div>
			    	<br/>	   
			    </div>

			    <div class="row">
			    	<label for="port_range" class="col-sm-4 control-label">Port Range:</label>
			    	<div class="col-sm-4">
			    		<input type="text" name="port_from" placeholder="From Port" class="form-control" required/>
			    	</div>
			    	<div class="col-sm-4">
			    		<input type="text" name="port_to" placeholder="To port" class="form-control" required/>
			    	</div> 
			    </div>
			    <div>
			    <div class="col-sm-offset-4 col-sm-8">Keep From & To Port same for single port access.</div>
			    </div>
			  			    
			    <div class="row">
			    	<label for="expiry" class="col-sm-4 control-label">Custom Access Expiry:</label>
			    	<div class="col-sm-4">
			    		<select name="expiry" class="form-control" required>
						  <option value="3600" selected>1 hour</option>
						  <option value="14400">4 hours</option>
						  <option value="43200">12 hours</option>
						  <option value="86400">1 Day</option>
						</select>
					</div>
				    <div class="col-sm-4">
				  		<button type="submit" class="btn btn-default">Get Access</button>
				  	</div>
			  	</div>
		    </form>

			<h2>Security Group Rules:</h2>
            Inbound Rules: 
	        <table class="table table-hover table-bordered">
	        <thead>
	          <tr>
	            <th>Protocol</th>
	            <th>Port</th>
	            <th>Source</th>
	          </tr>
	        </thead>
	        <tbody>
			@foreach($security_group['IpPermissions'] as $rule)
			   @foreach($rule['UserIdGroupPairs'] as $rule_group)
			    <tr>
			     	{{-- Checking for all traffic rule --}}
					@if("-1"!=$rule['IpProtocol'])
					<td>{{$rule['IpProtocol']}}</td>
					<td>{{$rule['FromPort']}}-{{$rule['ToPort']}}</td>
					@else
					<td>All</td>
					<td>All</td>
					@endif
					<td>Security Group: <a href="/manage/{{$rule_group['GroupId']}}">{{$rule_group['GroupId']}}</a></td>
			    </tr>
				@endforeach
			    @foreach($rule['IpRanges'] as $rule_ip)
			    <tr>
			    	{{-- Checking for all traffic rule --}}
			    	@if("-1"!=$rule['IpProtocol'])
					<td>{{$rule['IpProtocol']}}</td>
					<td>{{$rule['FromPort']}}-{{$rule['ToPort']}}</td>
					@else
					<td>All</td>
					<td>All</td>
					@endif
					<td>CIDR IP(s): {{$rule_ip['CidrIp']}}</td>
			    </tr>
				@endforeach
		    @endforeach 
		    </tbody>
		    </table>
		    Outbound Rules: <br/>
		    <table class="table table-hover table-bordered">
	        <thead>
	          <tr>
	            <th>Protocol</th>
	            <th>Port</th>
	            <th>Destination</th>
	          </tr>
	        </thead>
	        <tbody>
			@foreach($security_group['IpPermissionsEgress'] as $rule)
			    @foreach($rule['UserIdGroupPairs'] as $rule_group)
			    <tr>
			    	{{-- Checking for all traffic rule --}}
					@if("-1"!=$rule['IpProtocol'])
					<td>{{$rule['IpProtocol']}}</td>
					<td>{{$rule['FromPort']}}-{{$rule['ToPort']}}</td>
					@else
					<td>All</td>
					<td>All</td>
					@endif
					<td>Security Group: <a href="/manage/{{$rule_group['GroupId']}}">{{$rule_group['GroupId']}}</a></td>
				</tr>
				@endforeach
			    @foreach($rule['IpRanges'] as $rule_ip)
			    <tr>
			    	{{-- Checking for all traffic rule --}}
					@if("-1"!=$rule['IpProtocol'])
					<td>{{$rule['IpProtocol']}}</td>
					<td>{{$rule['FromPort']}}-{{$rule['ToPort']}}</td>
					@else
					<td>All</td>
					<td>All</td>
					@endif
					<td>CIDR IP(s): {{$rule_ip['CidrIp']}}</td>
				</tr>
				@endforeach
		    @endforeach
		    </tbody>
		    </table>
		    <br/>
		</div>    
	</div>
	<br/>
@stop