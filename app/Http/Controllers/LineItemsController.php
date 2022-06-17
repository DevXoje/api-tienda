<?php

namespace App\Http\Controllers;

use App\Models\LineItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LineItemsController extends ApiController
{
	public function __construct()
	{
		//$this->middleware(['auth:api'], ['except' => ['index', 'show', 'search']]); TODO: add auth middleware
	}

	public function index(Payment $payment, Request $request)
	{
		if (!$lineItems = $payment->lineItems()->get()) {
			return $this->errorResponse('Line Items do not exist.');
		}
		return $this->successResponse("", $lineItems);
	}


	public function store(Payment $payment, Request $request)
	{
		$validator = Validator::make($request->all(), [

		]);
		if (!$lineItem = LineItem::create($validator->validated())) {
			return $this->errorResponse('Line Item could not be created.');
		}
		return $this->successResponse("", $lineItem);
	}

	public function show(Payment $payment, LineItem $lineItem)
	{
		//
	}


	public function update(Request $request, LineItem $lineItem)
	{
		//
	}

	public function destroy(LineItem $lineItem)
	{
		//
	}
}
