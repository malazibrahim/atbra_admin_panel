@php
use App\CentralLogics\Helpers;
$max_processing_time = $order->restaurant?explode('-', $order->restaurant['delivery_time'])[0]:0;
@endphp
@extends('layouts.admin.app')


    @if($order->scheduled == 1)
        @section('scheduled')
    @elseif( in_array($order->order_status, ['confirmed','processing','handover']) )
        @section('processing')
    @elseif( in_array($order->order_status, ['refunded','refund_requested','refund_request_canceled']) )
        @section('refunded')
    @else
        @section($order->order_status)
    @endif

    active
    @endsection

@section('title', translate('Order_Details'))

@push('css_or_js')
    <link rel="stylesheet" type="text/css" href="{{dynamicAsset('public/assets/admin/css/daterangepicker.css')}}"/>
@endpush

@section('content')
    <?php $campaign_order = isset($order->details[0]->campaign) ? true : false;
        $subscription = isset($order->subscription_id) ? true : false;
        $reasons=\App\Models\OrderCancelReason::where('status', 1)->where('user_type' ,'admin' )->get();
    ?>

    <div id="content-disable" class="content container-fluid initial-39">
        <!-- Page Header -->
        <div class="page-header d-print-none">

            <h1 class="page-header-title text-capitalize">
                <div class="card-header-icon d-inline-flex mr-2 img">
                    <img src="{{dynamicAsset('/public/assets/admin/img/orders.png')}}" alt="public">
                </div>
                <span>
                    {{translate('messages.order_details')}}
                </span>
                <div class="d-flex ml-auto">
                    <a class="btn btn-icon btn-sm badge-soft-primary rounded-circle justify-content-center mr-1"
                        href="{{ route('admin.order.details', [$order['id'] - 1]) }}" data-toggle="tooltip"
                        data-placement="top" title="{{ translate('Previous_order') }}">
                        <i class="tio-chevron-left m-0"></i>
                    </a>
                    <a class="btn btn-icon btn-sm badge-soft-primary rounded-circle justify-content-center"
                        href="{{ route('admin.order.details', [$order['id'] + 1]) }}" data-toggle="tooltip"
                        data-placement="top" title="{{ translate('Next_order') }}">
                        <i class="tio-chevron-right m-0"></i>
                    </a>
                </div>
            </h1>

            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <div class="mt-2">
                        @php
                        $refund_amount = $order->order_amount - $order->delivery_charge - $order->dm_tips;
                        $refund= Helpers::get_business_data('refund_active_status');
                        @endphp
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row g-1" id="printableArea">
            <div class="col-lg-8 order-print-area-left">
                <!-- Card -->
                <div class="card mb-3 mb-lg-5">
                    <!-- Header -->
                    <div class="card-header border-0 align-items-start flex-wrap">
                        <div class="order-invoice-left">
                            <h1 class="page-header-title flex-wrap mt-2">
                                <span class="font--max-sm">{{ translate('messages.order_id_#')}}{{ $order['id'] }}</span>
                                <!-- Static -->
                                @if ($order->edited)
                                    <span class="badge badge-soft-danger text-capitalize my-2 ml-2">
                                        {{ translate('messages.edited') }}
                                    </span>
                                @endif

                                @if ($order->orderEditLogs && count($order->orderEditLogs) > 0)

                                <a href="#0" class="w-sm-auto w-100 fs-12 font-medium hover-info ml-2 offcanvas-trigger" data-target="#offcanvas__history_log">({{ translate('Edit History Log') }})</a>
                                @endif
                                <!-- Static -->
                                <div class="d-sm-none d-flex flex-wrap ml-auto align-items-center justify-content-end initial-39-2">
                                    @if (!$subscription && !$campaign_order  && $order->payment_method == 'cash_on_delivery'  &&  in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted']) && $order->restaurant)
                                    <button class="btn bn--primary btn-outline-primary m-1 print--btn "data-toggle="modal" data-target="#edit-order" type="button">
                                        <i class="tio-edit m-0"></i>
                                        <span>{{ translate('messages.edit_Item') }}</span>
                                    </button>
                                    @endif
                                    <a class="btn btn-primary m-1 print--btn" href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                        <i class="tio-print mr-0"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                    </a>
                                </div>
                            </h1>
                            <span class="mt-2 d-block text--title">
                                {{ translate('messages.Placed_Date') }} :
                                <span class="font-semibold">
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order['created_at'])) }}</span>
                            </span>
                            @if ($order->order_type == 'dine_in')
                            <span class="mt-2 d-block text--title">
                                {{ translate('messages.Din-in_Date') }} :
                                <span class="font-semibold">
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order['schedule_at'])) }}
                                </span>
                            </span>
                            @endif
                            @if ($subscription)
                            <span>
                                <strong class="text-primary"> {{ translate('messages.subscription_order') }}</strong>
                            </span>
                            <br>
                            @endif
                            @if ($order->schedule_at && ($order->scheduled || $subscription))
                                <span>
                                    <span>{{ translate('messages.scheduled_at') }} :</span>
                                    <strong class="text-warning">
                                        {{ date('d M Y ' . config('timeformat'), strtotime($order['schedule_at'])) }}
                                    </strong>
                                </span>
                            @endif
                            @if (isset($order->restaurant))
                                <h6 class="mt-2 pt-1 mb-2">
                                    <i class="tio-shop"></i>
                                    {{ translate('messages.restaurant') }} : <label
                                        class="badge badge-soft-info font-regular m-0">{{ Str::limit($order->restaurant->name, 25, '...') }}</label>
                                </h6>
                            @else
                                <h6 class="mt-2 pt-1 mb-2">
                                    <i class="tio-shop"></i>
                                    {{ translate('messages.restaurant') }} : <label
                                        class="badge badge-soft-danger font-regular m-0">{{ Str::limit(translate('messages.Restaurant_deleted!'), 25, '...') }}</label>
                                </h6>
                            @endif
                                <h6 class="m-0">
                            @if ($campaign_order)
                                    <span class="badge badge-soft-primary ml-sm-3">
                                        {{ translate('messages.campaign_order') }}
                                    </span>
                            @endif
                                </h6>
                                <div class="hs-unfold mt-2">
                                    <button class="btn order--details-btn-sm btn--primary btn-outline-primary btn--sm" data-toggle="modal" data-target="#locationModal"><i
                                            class="tio-poi-outlined"></i> <span class="ml-1">{{ translate('messages.show_locations_on_map') }}</span> </button>

                                </div>
                            <div class="order--note mt-3">

                            @if($order['order_note'])
                            <span  class="bg--F6F6F6 rounded py-1 px-2 fs-12 mt-3 d-flex align-items-center gap-1 font-regular m-0"><strong
                                    class="text-dark font-semibold">{{ translate('messages.Note:') }}</strong> {{ $order['order_note'] }}</span>
                            @endif


                                @if($order?->offline_payments && $order?->offline_payments->status == 'denied' && $order?->offline_payments->note )
                                <h6 class="w-100 badge-soft-warning">
                                    <span class="text-dark">
                                        {{ translate('messages.Offline_payment_rejection_note') }} :
                                    </span>
                                        {{  $order?->offline_payments->note }}
                                    </h6>
                                @endif
                                @if ($order['unavailable_item_note'])
                                <h6 class="my-2 ml-2">
                                    <span class="text--title">
                                        {{ translate('messages.if_item_is_not_available') }} :
                                    </span>
                                    {{ translate($order->unavailable_item_note) }}
                                </h6>
                                @endif
                                @if ($order['delivery_instruction'])
                                    <h6 class="my-2 ml-2">
                                        <span class="text--title">
                                            {{ translate('messages.order_delivery_instruction') }} :
                                        </span>
                                        {{ translate($order->delivery_instruction)  }}
                                    </h6>
                                @endif
                            </div>
                        </div>
                        <div class="order-invoice-right">
                            <div class="d-none d-sm-flex flex-wrap ml-auto align-items-center justify-content-end initial-39-1">
                                @if (!$subscription && !$campaign_order && $order->payment_method == 'cash_on_delivery'  &&  in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted']) && $order->restaurant)
                                    <button class="btn bn--primary btn-outline-primary m-2 print--btn  "
                                       data-toggle="modal" data-target="#edit-order"type="button">
                                        <i class="tio-edit"></i>
                                        <span>{{ translate('messages.edit_Item') }}</span>
                                    </button>
                                @endif
                                <a class="btn btn-primary m-2 print--btn" href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-0"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                            </div>
                            <div class="text-right mt-3 order-invoice-right-contents text-capitalize">
                                <h6>
                                    <span>{{ translate('messages.order_type') }} :</span>
                                    <strong>{{   translate(str_replace('_', ' ', $order['order_type'])) }}</strong>
                                </h6>

                                @if (isset($order->subscription))
                                        <h6>
                                            <span>{{ translate('messages.Subscription_status') }} :</span>
                                                @if ($order->subscription->status == 'active')
                                                <span class="badge badge-soft-success ">
                                                <span class="legend-indicator bg-success"></span>{{translate('messages.'.$order->subscription->status)}}
                                                </span>
                                                @elseif ($order->subscription->status == 'paused')
                                                <span class="badge badge-soft-primary">
                                                <span class="legend-indicator bg-danger"></span>{{translate('messages.'.$order->subscription->status)}}
                                                </span>
                                                @else
                                                <span class="badge badge-soft-primary ">
                                                <span class="legend-indicator bg-info"></span>{{translate('messages.'.$order->subscription->status)}}
                                                </span>
                                                @endif
                                        </h6>
                                @endif

                                <h6>
                                    @if (isset($order->subscription) && $order->subscription->status != 'canceled' )
                                        @php
                                        $order->order_status = $order->subscription_log ? $order->subscription_log->order_status : $order->order_status;
                                        @endphp
                                    @endif

                                    <span>{{ translate('messages.status') }} :</span>
                                    @if ($order['order_status'] == 'pending')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize font-medium">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($order['order_status'] == 'confirmed')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize font-medium">
                                            {{ translate('messages.confirmed') }}
                                        </span>
                                    @elseif($order['order_status'] == 'processing')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize font-medium">
                                            {{ translate('messages.processing') }}
                                        </span>
                                    @elseif($order['order_status'] == 'picked_up')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize font-medium">
                                            {{ translate('messages.out_for_delivery') }}
                                        </span>
                                    @elseif($order['order_status'] == 'delivered')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize font-medium">
                                            {{$order?->order_type == 'dine_in' ? translate('messages.Completed') : translate('messages.delivered')}}

                                        </span>
                                    @elseif($order['order_status'] == 'failed')
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize font-medium">
                                            {{ translate('messages.payment_failed') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize font-medium">
                                            {{ translate($order['order_status']) }}
                                        </span>
                                    @endif



                                </h6>
                                <h6>
                                    <span>{{ translate('messages.payment_method') }} :</span>
                                    <strong>{{   translate(str_replace('_', ' ', $order['payment_method']))  }}</strong>
                                </h6>
                                <h6>
                                    <span>{{translate('messages.payment_status')}} :</span>
                                    @if ($order['payment_status'] == 'paid')
                                    <strong class="text-success">{{ translate('messages.paid') }}</strong>
                                    @elseif ($order['payment_status'] == 'partially_paid')

                                        @if ($order->payments()->where('payment_status','unpaid')->exists())
                                        <strong class="text-danger">{{ translate('messages.partially_paid') }}</strong>
                                        @else
                                        <strong class="text-success">{{ translate('messages.paid') }}</strong>
                                        @endif
                                    @else
                                        <strong class="text-danger">{{ translate('messages.unpaid') }}</strong>
                                    @endif
                                </h6>


                                    <!-- offline_payment -->
                                    @if($order?->offline_payments)
                                        <span>{{ translate('Payment_verification') }}</span> <span>:</span>
                                        @if ($order?->offline_payments->status == 'pending')
                                            <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.pending') }}
                                            </span>
                                        @elseif ($order?->offline_payments->status == 'verified')
                                            <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.verified') }}
                                            </span>
                                        @elseif ($order?->offline_payments->status == 'denied')
                                            <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.denied') }}
                                            </span>
                                        @endif

                                        @foreach (json_decode($order->offline_payments->payment_info) as $key=>$item)
                                            @if ($key != 'method_id')
                                                <h6 class="">
                                                    <div class="d-flex justify-content-sm-end text-capitalize">
                                                        <span class="title-color">{{translate($key)}} :</span>
                                                        <strong>{{ $item }}</strong>
                                                    </div>
                                                </h6>
                                            @endif
                                        @endforeach
                                    @endif
                                <h6>
                                    @if ($order['transaction_reference'] == null)
                                        <span>{{ translate('messages.reference_code') }} :</span>
                                        @if (isset($order->restaurant))
                                            <button class="btn btn-outline-primary btn--primary btn-sm add--referal" data-toggle="modal"
                                                data-target=".bd-example-modal-sm">
                                                {{ translate('messages.add') }}
                                            </button>
                                        @endif
                                    @else
                                        <span>{{ translate('messages.reference_code') }} :</span>
                                        <strong>{{ $order['transaction_reference'] }}</strong>
                                    @endif
                                </h6>
                                @if ($order->coupon)
                                    <h6>
                                        <span>{{ translate('messages.coupon') }}</span>
                                        <label class="text-info">{{ $order->coupon_code }}
                                            ({{ translate('messages.' . $order->coupon->coupon_type) }})</label>
                                    </h6>
                                @endif

                                @if ($order->cutlery)
                                <h6>
                                    <span>{{ translate('cutlery') }}</span> <span>:</span>
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.yes') }}
                                        </span>
                                </h6>
                                @else
                                <h6>
                                    <span>{{ translate('cutlery') }}</span> <span>:</span>
                                        <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.No') }}
                                        </span>
                                </h6>

                                @endif
                            </div>
                        </div>
                    </div>


                    <!-- Body -->
                    <div class="card-body pb-0 px-0">
                            <?php
                                $total_addon_price = 0;
                                $product_price = 0;
                                $details = $order->details;
                            ?>
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                            <thead class="thead-light">
                                <th>{{translate('sl')}}</th>
                                <th>{{translate('item_details')}}</th>
                                <th>{{translate('addons')}}</th>
                                <th class="text-right">{{translate('price')}}</th>
                            </thead>
                                <tbody>
                                    @forelse ($details as $key => $detail)
                                        @if (isset($detail->food_id) )
                                            <?php

                                            $deleted_food = $detail->food == null?1:0;
                                            $detail->food = json_decode($detail->food_details, true);
                                            $food = \App\Models\Food::where(['id' => $detail?->food['id']])->with('storage')->select('id','image')->first();
                                            ?>
                                            <!-- Media -->
                                            <tr>
                                                <td>
                                                    <!-- Static Count Number -->
                                                    <div>
                                                        {{$key+1}}
                                                    </div>
                                                    <!-- Static Count Number -->
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                            @if(!$deleted_food)
                                                                <a class="avatar avatar-xl mr-3"
                                                                href="{{ route('admin.food.view', $detail->food['id']) }}">
                                                                    <img class="img-fluid rounded onerror-image"
                                                                        src="{{ $food['image_full_url'] ?? dynamicAsset('public/assets/admin/img/100x100/food-default-image.png') }}"
                                                                        data-onerror-image="{{dynamicAsset('public/assets/admin/img/100x100/food-default-image.png')}}"
                                                                        alt="Image Description">
                                                                    </a>
                                                            @else
                                                                <div class="avatar avatar-xl mr-3">
                                                                    <img class="img-fluid rounded"
                                                                        src="{{ dynamicAsset('public/assets/admin/img/100x100/food-default-image.png') }}"
                                                                        alt="Image Description">
                                                                    </div>
                                                            @endif
                                                        <div class="media-body">
                                                            <div>
                                                                <strong class="line--limit-1"> {{ $detail->food == null?'Not Found':$detail->food['name'] }}</strong>
                                                                @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [] )
                                                                    @foreach(json_decode($detail['variation'],true) as  $variation)
                                                                        @if (isset($variation['name'])  && isset($variation['values']))
                                                                                <span class="d-block text-capitalize">
                                                                                    <strong>
                                                                                        {{  $variation['name']}} -
                                                                                    </strong>
                                                                                </span>
                                                                                @foreach ($variation['values'] as $value)
                                                                                        <span class="d-block text-capitalize">
                                                                                            &nbsp;   &nbsp; {{ $value['label']}} :
                                                                                            <strong>{{Helpers::format_currency( $value['optionPrice'])}}</strong>
                                                                                        </span>
                                                                                @endforeach
                                                                        @else
                                                                                    @if (isset(json_decode($detail['variation'],true)[0]))
                                                                                        <strong><u> {{  translate('messages.Variation') }} : </u></strong>
                                                                                        @foreach(json_decode($detail['variation'],true)[0] as $key1 =>$variation)
                                                                                            <div class="font-size-sm text-body">
                                                                                                <span>{{$key1}} :  </span>
                                                                                                <span class="font-weight-bold">{{$variation}}</span>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    @endif
                                                                        @endif
                                                                    @endforeach
                                                                @endif

                                                                <h6>
                                                                    {{translate('qty')}} : {{ $detail['quantity'] }}
                                                                </h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                            <div class="font-size-sm text-body">
                                                                <span>{{ Str::limit($addon['name'], 20, '...') }} : </span>
                                                                <span class="font-weight-bold">
                                                                    {{ $addon['quantity'] }} x
                                                                    {{ Helpers::format_currency($addon['price']) }}
                                                                </span>

                                                            </div>
                                                            @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div>
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ Helpers::format_currency($amount) }}</h5>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php($product_price += $amount)

                                        @elseif(isset($detail->item_campaign_id))
                                            <?php
                                            $deleted_food = $detail->campaign == null?1:0;
                                            $detail->campaign = json_decode($detail->food_details, true);
                                            $campaign = \App\Models\ItemCampaign::where(['id' => $detail->campaign['id']])->select('id','image')->first();
                                            ?>

                                            <tr>
                                                <td>
                                                    <div>
                                                        {{$key+1}}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="media media--sm">
                                                            @if(!$deleted_food)
                                                                <a class="avatar avatar-xl mr-3"
                                                                href="{{ route('admin.campaign.view', ['item', $detail->campaign['id']]) }}">
                                                                <img class="img-fluid rounded onerror-image"
                                                                    src="{{ $campaign['image_full_url'] ?? dynamicAsset('public/assets/admin/img/100x100/food-default-image.png') }}"
                                                                    data-onerror-image="{{dynamicAsset('public/assets/admin/img/100x100/food-default-image.png')}}"
                                                                    alt="Image Description">
                                                                </a>
                                                            @else
                                                                <div class="avatar avatar-xl mr-3">
                                                                    <img class="img-fluid"
                                                                        src="{{ dynamicAsset('public/assets/admin/img/100x100/food-default-image.png') }}"
                                                                        alt="Image Description">
                                                                </div>
                                                            @endif
                                                        <div class="media-body">
                                                            <div>
                                                                <strong class="line--limit-1"> {{ $detail->campaign == null?'Not Found':$detail->campaign['name'] }}</strong>
                                                                @if (count(json_decode($detail['variation'], true)) > 0)
                                                                @foreach(json_decode($detail['variation'],true) as  $variation)
                                                                @if ( isset($variation['name'])  && isset($variation['values']))
                                                                    <span class="d-block text-capitalize">
                                                                            <strong>
                                                                        {{  $variation['name']}} -
                                                                            </strong>
                                                                    </span>
                                                                    @foreach ($variation['values'] as $value)
                                                                    <span class="d-block text-capitalize">
                                                                        &nbsp;   &nbsp; {{ $value['label']}} :
                                                                        <strong>{{Helpers::format_currency( $value['optionPrice'])}}</strong>
                                                                        </span>
                                                                    @endforeach
                                                                @else
                                                                    @if (isset(json_decode($detail['variation'],true)[0]))
                                                                    <strong><u> {{  translate('messages.Variation') }} : </u></strong>
                                                                        @foreach(json_decode($detail['variation'],true)[0] as $key1 =>$variation)
                                                                            <div class="font-size-sm text-body">
                                                                                <span>{{$key1}} :  </span>
                                                                                <span class="font-weight-bold">{{$variation}}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    @endif
                                                                        @break
                                                                @endif
                                                                        @endforeach
                                                            @endif
                                                                <h6>
                                                                    {{ $detail['quantity'] }} x {{ Helpers::format_currency($detail['price']) }}
                                                                </h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                            @if ($key2 == 0)
                                                                <strong><u>{{ translate('messages.addons') }} : </u></strong>
                                                            @endif
                                                            <div class="font-size-sm text-body">
                                                                <span class="font-weight-bold">
                                                                    {{ $addon['quantity'] }} x
                                                                    {{ Helpers::format_currency($addon['price']) }}
                                                                </span>
                                                                <span>{{ Str::limit($addon['name'], 20, '...') }} : </span>
                                                            </div>
                                                            @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div>
                                                        @php($amount = $detail['price'] * $detail['quantity'])
                                                        <h5>{{ Helpers::format_currency($amount) }}</h5>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php($product_price += $amount)
                                            <!-- End Media -->
                                        @endif
                                        @empty
                                            <tr>
                                                <td>
                                                    {{ translate('Food_was_deleted') }}

                                                </td>
                                            </tr>
                                    @endforelse
                                </tbody>
                        </table>
                    </div>
                        <hr class="mt-0">

                        <?php
                        $total_tax_amount = $order['total_tax_amount'];
                        if($order->tax_status == 'included'){
                            $total_tax_amount = 0;
                        }
                        ?>

                        <div class="row md-row-reverse justify-content-between align-items-center px-4 mb-3">
                            <div class="col-md-6 col-lg-5">

                            </div>
                            <div class="col-md-6 col-lg-5">
                                <dl class="row">
                                    <dt class="col-6 text-capitalize">{{ translate('messages.items_price') }}:</dt>
                                    <dd class="col-6 text-right">
                                        {{ Helpers::format_currency($product_price) }}</dd>
                                    <dt class="col-6">{{ translate('messages.addon_cost') }}:</dt>
                                    <dd class="col-6 text-right">
                                        {{ Helpers::format_currency($total_addon_price) }}

                                    </dd>

                                    <dt class="col-6">{{ translate('messages.subtotal') }}
                                    @if ($order->tax_status == 'included')
                                    ({{ translate('messages.TAX_Included') }})
                                    @endif
                                        :</dt>
                                    <dd class="col-6 text-right">
                                        {{ Helpers::format_currency($product_price + $total_addon_price) }}
                                    </dd>
                                    <dt class="col-6">{{ translate('messages.discount') }}:</dt>
                                    <dd class="col-6 text-right">
                                        - {{ Helpers::format_currency($order['restaurant_discount_amount']) }}
                                    </dd>
                                    <dt class="col-6">{{ translate('messages.coupon_discount') }}:</dt>
                                    <dd class="col-6 text-right">
                                        - {{ Helpers::format_currency($order['coupon_discount_amount']) }}
                                    </dd>

                                    @if ($order['ref_bonus_amount'] > 0)
                                    <dt class="col-6">{{ translate('messages.Referral_Discount') }}:</dt>
                                    <dd class="col-6 text-right">
                                        - {{ Helpers::format_currency($order['ref_bonus_amount']) }}
                                    </dd>
                                    @endif


                                    @if ($order->tax_status == 'excluded' || $order->tax_status == null  )
                                    <dt class="col-6">{{ translate('messages.vat/tax') }}:</dt>
                                    <dd class="col-6 text-right">
                                        +
                                        {{ Helpers::format_currency($total_tax_amount) }}
                                    </dd>
                                    @endif
                                    @if ($order['dm_tips'] > 0)
                                    <dt class="col-6">{{ translate('DM_Tips') }}</dt>
                                    <dd class="col-6 text-right">
                                        + {{ Helpers::format_currency($order['dm_tips']) }}</dd>
                                    @endif



                                    <dt class="col-6">
                                        {{ translate('Delivery_Fee') }}
                                        @if($order['additional_charge']==0)
                                            <hr>
                                        @endif
                                    </dt>
                                    <dd class="col-6 text-right">
                                        + {{ Helpers::format_currency($order['delivery_charge']) }}
                                        @if($order['additional_charge']==0)
                                            <hr>
                                        @endif
                                    </dd>


                                        @if ($order['additional_charge'] > 0)
                                            <dt class="col-6">
                                                {{ Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}
                                                <hr>
                                            </dt>
                                            <dd class="col-6 text-right">
                                                + {{ Helpers::format_currency($order['additional_charge']) }}
                                                <hr>
                                            </dd>
                                        @endif



                                        @if ($order['extra_packaging_amount'] > 0)
                                        <dt class="col-6">{{ translate('messages.Extra_Packaging_Amount') }}:</dt>
                                        <dd class="col-6 text-right">
                                            + {{ Helpers::format_currency($order['extra_packaging_amount']) }}
                                        </dd>
                                        @endif



                                    @if ($order?->payments)
                                        @foreach ($order?->payments as $payment)
                                            @if ($payment->payment_status == 'paid')
                                                @if ( $payment->payment_method == 'cash_on_delivery')

                                                <dt class="col-6">{{ translate('messages.Paid_with_Cash') }} ({{  translate('COD')}}) :</dt>
                                                @else

                                                <dt class="col-6">{{ translate('messages.Paid_by') }} {{  translate($payment->payment_method)}} :</dt>
                                                @endif
                                            @else

                                            <dt class="col-6">{{ translate('Due_Amount') }} ({{  $payment->payment_method == 'cash_on_delivery' ?  translate('messages.COD') : translate($payment->payment_method) }}) :</dt>
                                            @endif
                                        <dd class="col-6 text-right">
                                            {{ Helpers::format_currency($payment->amount) }}
                                        </dd>
                                        @endforeach
                                    @endif
                                </dl>
                            </div>


                        </div>

                        <div id="stickyDiv" class="bg-white pb-1 mt-n25 py-2 mb-3 {{ $order->bring_change_amount > 0 ? 'position-sticky bottom-0' : '' }}">
                            <div class="row  md-row-reverse justify-content-between align-items-center px-4">
                                <div class="col-md-6 col-lg-5">
                                    @if($order->bring_change_amount > 0)
                                        <div class="alert fs-12 p-2 alert-primary-light text-dark mb-0 mt-3 mt-md-0 row" role="alert">
                                            {{translate('Please instruct the delivery man to collect '.Helpers::format_currency($order->bring_change_amount).' in change upon delivery')}}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6 col-lg-5">
                                    <dl class="row align-items-center font-weight-bold text--title">
                                        <dd class="col-6 mb-0">{{ translate('messages.total') }}:</dd>
                                        <dt class="col-6 text-right">
                                            {{ Helpers::format_currency($order->order_amount) }}
                                        </dt>
                                    </dl>
                                </div>
                            </div>
                        </div>



                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 order-print-area-right">
                @if ($order->order_status == 'canceled')
                <div  class="col-12">
                    <div class="card ">
                        <div class="card-body pt-2">
                            <ul class="delivery--information-single mt-3">
                                <li>
                                    <span class=" badge badge-soft-danger "> {{ translate('messages.Cancel_Reason') }} :</span>
                                    <span class="info">  {{ $order->cancellation_reason }} </span>
                                </li>
                                <hr class="w-100">
                                <li>
                                    <span class="name">{{ translate('Cancel_Note') }} </span>
                                    <span class="info">  {{ $order->cancellation_note ?? translate('messages.N/A')}} </span>
                                </li>
                                <li>
                                    <span class="name">{{ translate('Canceled_By') }} </span>
                                    <span class="info">  {{ translate($order->canceled_by) }} </span>
                                </li>
                                @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid' )
                                        @if ( $order?->payments)
                                            @php( $pay_infos =$order->payments()->where('payment_status','paid')->get())
                                            @foreach ($pay_infos as $pay_info)
                                                <li>
                                                    <span class="name">{{ translate('Amount_paid_by') }} {{ translate($pay_info->payment_method) }} </span>
                                                    <span class="info">  {{ Helpers::format_currency($pay_info->amount)  }} </span>
                                                </li>
                                            @endforeach
                                        @else
                                        <li>
                                            <span class="name">{{ translate('Amount_paid_by') }} {{ translate($order->payment_method) }} </span>
                                            <span class="info ">  {{ Helpers::format_currency($order->order_amount)  }} </span>
                                        </li>
                                        @endif
                                @endif

                                @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid')
                                    @if ( $order?->payments)
                                        @php( $amount =$order->payments()->where('payment_status','paid')->sum('amount'))
                                            <li>
                                                <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                                                <span class="info">  {{ Helpers::format_currency($amount)  }} </span>
                                            </li>
                                    @else
                                    <li>
                                        <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                                        <span class="info">  {{ Helpers::format_currency($order->order_amount)  }} </span>
                                    </li>
                                    @endif
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                @endif



                @if (!empty($order->refund))
                    @if ( $order->order_status == 'refund_requested' || $order->order_status == 'refunded' || $order->order_status == 'refund_request_canceled')
                   <div class="col-12">
                    <div class="card mb-2">
                        <div class="card-header border-0 d-block text-center pb-0">
                            <h4 class="m-0" >{{ translate('messages.Refund_Request') }} </h4>
                            <span>
                                {{ date('d M Y ' . config('timeformat'), strtotime($order->refund->created_at))  }}
                            </span>

                            @if ($order->order_status == 'refund_requested')
                                <span class="badge __badge badge-primary __badge-abs">{{ translate('messages.pending') }}</span>
                                @elseif($order->order_status == 'refunded')
                                <span class="badge __badge badge-info __badge-abs">{{ translate('messages.refunded') }}</span>
                                @elseif($order->refund->order_status == 'refund_request_canceled')
                                <span class="badge __badge-pill badge-danger __badge-abs">{{ translate('messages.rejected') }}</span>
                            @endif

                        </div>
                        <div class="card-body pt-2">

                            <label class="input-label" for="exampleFormControlInput1">{{translate('messages.image')}} : </label>
                            <div class="row g-3">
                                @php( $data=  (isset($order->refund->image)) ? json_decode($order->refund->image,true)  : 0 )
                                @if ($data)
                                    @foreach($data as $key=>$img)
                                    @php($img = is_array($img)?$img:['img'=>$img,'storage'=>'public'])
                                    <div class="col-3">
                                        <img class="img__aspect-1 rounded border w-100 onerror-image"
                                             data-toggle="modal"
                                             data-target="#imagemodal{{ $key }}"
                                             data-onerror-image="{{ dynamicAsset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             src="{{ Helpers::get_full_url('refund',$img['img'],$img['storage']) }}">
                                </div>
                                    <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1" role="dialog"
                                    aria-labelledby="myModalLabel{{ $key }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="myModalLabel{{ $key }}">
                                                    {{ translate('Refund Image') }}</h4>
                                                <button type="button" class="close" data-dismiss="modal"><span
                                                        aria-hidden="true">&times;</span><span
                                                        class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <img src="{{ Helpers::get_full_url('refund',$img['img'],$img['storage']) }}"
                                                    class="initial--22 w-100">
                                            </div>
                                            @php($storage = $img['storage']??'public')
                                            @php($file = $storage == 's3'?base64_encode('refund/' . $img['img']):base64_encode('public/refund/' . $img['img']))
                                            <div class="modal-footer">
                                                <a class="btn btn-primary"
                                                    href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                        class="tio-download"></i> {{ translate('messages.download') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    @endforeach
                                    @else
                                    <div class="col-3">
                                        <img class="img__aspect-1 rounded border w-100"
                                        src="{{ dynamicAsset('public/assets/admin/img/160x160/img2.jpg') }}">
                                    </div>
                                @endif
                            </div>
                            <hr>


                            <ul class="delivery--information-single mt-3">
                                <li>
                                    <span class="name">{{ translate('Reason') }} </span>
                                    <span class="info">  {{ $order->refund->customer_reason }} </span>
                                </li>
                                <li>
                                    <span class="name">{{ translate('amount') }} </span>
                                    <span class="info"> {{ $order->refund->refund_amount }}</span>
                                </li>
                                <li>
                                    <span class="name">{{ translate('Method') }} </span>
                                    <span class="info"> {{ $order->refund->refund_method }}</span>
                                </li>
                                <li>
                                    <span class="name"> {{ translate('Status') }} </span>
                                    <span class="info"> {{ $order->refund->refund_status }}</span>
                                </li>
                                @if (isset($order->refund->admin_note))
                                <li>
                                    <span class="name">   {{ translate('Admin_Note') }} </span>
                                    <span class="info">  {{ $order->refund->admin_note ?? 'No Note'}}</span>
                                </li>
                                @endif
                                @if (isset($order->refund->customer_note))
                                <li>
                                    <span class="name">  {{ translate('Customer_Note') }} </span>
                                    <span class="info">   {{ $order->refund->customer_note ?? 'No Note' }}</span>
                                </li>
                                @endif
                                <hr class="w-100">
                            </ul>

                            <div class="btn--container refund--btn">
                                @if ($refund  == true &&  $order->order_status == 'refund_requested')
                                <button type="button" class="btn btn--danger btn-outline-danger" data-toggle="modal" data-target="#refund_cancelation_note">
                                <i class="tio-money"></i> <span class="ml-1">{{ translate('messages.Cancel_Refund') }}</span> </button>
                                @endif
                             @if ( $refund  == true &&  $order->payment_status == 'paid' && $order->order_status != 'refunded'  )
                                    @if ($order->order_status == 'refunded' )

                                    @else
                                    <button class="btn btn--primary btn--sm route-alert"
                                    data-url="{{ route('admin.order.status', ['id' => $order['id'],
                                    'order_status' => 'refunded']) }}" data-message="{{ translate('messages.you_want_to_refund_this_order', ['amount' => $refund_amount . ' ' . Helpers::currency_code()]) }}" data-title="{{ translate('messages.are_you_sure_want_to_refund') }}"><i
                                        class="tio-money"></i> <span class="ml-1">{{ translate('messages.Refund') }}</span> </button>

                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                    @endif
                @endif

                <div class="row g-1">
                            @if (isset($order->restaurant))
                                <div class="col-12 ">
                                    @if($order?->offline_payments && !in_array($order->order_status, ['canceled']))
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <div class="card border-info text-center  mb-2">
                                                    <div class="card-body">
                                                        <h2>
                                                            @if ($order?->offline_payments->status == 'verified')
                                                            {{ translate('Payment_Verified') }}
                                                            @elseif($order?->offline_payments->status == 'denied')

                                                            {{ translate('Payment_Verification_Denied') }}
                                                            @else
                                                            {{ translate('Payment_Verification_Needed ') }}
                                                            @endif
                                                        </h2>

                                                        @if ($order?->offline_payments->status == 'pending')
                                                            <p class="text-danger"> {{ translate('Order_amount_is_paid_via_offline_payment_method._Please_Verify_the_payment_before_confirm_order.') }}</p>
                                                            <div class="btn--container justify-content-center">
                                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Verify_Payment') }}</button>
                                                            </div>
                                                        @elseif($order?->offline_payments->status == 'verified')
                                                        <p class="text-success"> {{ translate('Payment_is_verified_by_admin.') }}</p>
                                                            <div class="btn--container justify-content-center">
                                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Payment_Details') }}</button>
                                                            </div>
                                                        @elseif($order?->offline_payments->status == 'denied')
                                                            <p class="text-danger"> {{ translate('Please_take_actions_before_confirming_the_order.') }}</p>

                                                            <div class="btn--container justify-content-center">
                                                                <button  type="button" class="btn btn--danger btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Recheck') }}</button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @if ($order->offline_payments == null  || ($order?->offline_payments && $order?->offline_payments->status == 'verified'))
                                    <!-- Card -->
                                    @if(empty($order->refund))
                                    @if (!in_array($order['order_status'], ['delivered','take_away','refund_requested','canceled','refunded','refund_request_canceled']) )
                                    <div class="card mb-2">
                                        <!-- Header -->
                                                        <div class="card-header border-0 justify-content-center pt-4 pb-0">
                                                            <h4 class="card-header-title">{{translate('order_Setup')}}</h4>
                                                        </div>


                                                        <!-- End Header -->
                                                        <!-- Body -->
                                                        <div class="card-body">
                                                        <label class="form-label">{{translate('change_order_status')}}</label>
                                                        <!-- Unfold -->
                                                        <div>
                                                            <div class="dropdown">
                                                                @if (isset($order->restaurant))
                                                                    <button class="form-control h--45px dropdown-toggle d-flex justify-content-between align-items-center" type="button"
                                                                        id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                                        aria-expanded="false">

                                                                        <?php
                                                                            $message= match($order['order_status']){
                                                                                            'pending' => translate('messages.pending'),
                                                                                            'confirmed' => translate('messages.confirmed'),
                                                                                            'accepted' => translate('messages.accepted'),
                                                                                            'processing' => translate('messages.processing'),
                                                                                            'handover' => translate('messages.handover'),
                                                                                            'picked_up' => translate('messages.out_for_delivery'),
                                                                                            'delivered' => $order['order_type'] == 'dine_in' ? translate('messages.Completed') : translate('messages.delivered')                                                                                    ,
                                                                                            'canceled' => translate('messages.canceled'),
                                                                                            default => translate('messages.status') ,
                                                                                        };
                                                                        ?>
                                                                        {{ $message }}
                                                                    </button>
                                                                @endif
                                                                    @php($order_delivery_verification = (bool) Helpers::get_business_data('order_delivery_verification') )
                                                                    <div class="dropdown-menu text-capitalize" aria-labelledby="dropdownMenuButton">
                                                                        <a class="dropdown-item route-alert {{ $order['order_status'] == 'pending' ? 'active' : '' }}"
                                                                            data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'pending']) }}" data-message="{{ translate('Change_status_to_pending_?') }}"
                                                                            href="javascript:">{{ translate('messages.pending') }}</a>
                                                                        <a class="dropdown-item route-alert {{ $order['order_status'] == 'confirmed' ? 'active' : '' }}"
                                                                            data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'confirmed']) }}" data-message="{{ translate('Change_status_to_confirmed_?') }}"
                                                                            href="javascript:">{{ translate('messages.confirmed') }}</a>

                                                                        <a class="dropdown-item route-alert {{ $order['order_status'] == 'processing' ? 'active' : '' }}"
                                                                            data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'processing']) }}" data-message="{{ translate('Change_status_to_processing_?') }}" data-title="{{ translate('Are_you_sure?') }}" data-processing="{{ $max_processing_time }}"
                                                                            href="javascript:">
                                                                            {{ translate('messages.processing') }}</a>

                                                                        <a class="dropdown-item route-alert {{ $order['order_status'] == 'handover' ? 'active' : '' }}"
                                                                            data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'handover']) }}" data-message="{{ translate('Change_status_to_handover_?') }}"
                                                                            href="javascript:">{{ translate('messages.handover') }}</a>
                                                                        @if ($order['order_type'] == 'delivery')
                                                                        <a class="dropdown-item route-alert {{ $order['order_status'] == 'picked_up' ? 'active' : '' }}"
                                                                            data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'picked_up']) }}" data-message="{{ translate('Change_status_to_out_for_delivery_?') }}"
                                                                            href="javascript:">{{ translate('messages.out_for_delivery') }}</a>
                                                                        @endif

                                                                        <a class="dropdown-item route-alert {{ $order['order_status'] == 'delivered' ? 'active' : '' }}"
                                                                            data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'delivered']) }}" data-message="{{ translate('Change_status_to_delivered_(payment_status_will_be_paid_if_not)_?') }}"
                                                                            href="javascript:"> {{$order?->order_type == 'dine_in' ? translate('messages.Completed') : translate('messages.delivered')}}
                                                                        </a>
                                                                        <a class="dropdown-item cancelled_status {{ $order['order_status'] == 'canceled' ? 'active' : '' }}"
                                                                            >{{ translate('messages.canceled') }}</a>
                                                                    </div>
                                                            </div>
                                                        </div>

                                                        <!-- End Unfold -->
                                                        <!-- Static -->
                                                        @if (!in_array($order['order_type'],['dine_in','take_away']) && !$order->delivery_man &&
                                                            (isset($order->restaurant) &&   ($order->restaurant->restaurant_model == 'commission'
                                                            && !$order->restaurant->self_delivery_system ) ||  ($order->restaurant->restaurant_model == 'subscription'
                                                            && isset($order->restaurant->restaurant_sub) && $order->restaurant->restaurant_sub->self_delivery == 0)))
                                                            <div class="w-100 text-center mt-4">
                                                                <button type="button" class="btn w-100 btn-primary font-regular" data-toggle="modal"
                                                                    data-target="#myModal" data-lat='21.03' data-lng='105.85'>
                                                                    <i class="tio-bike"></i> {{ translate('messages.assign_delivery_man') }}
                                                                </button>
                                                            </div>
                                                        @endif



                                                    </div>
                                                    @endif
                                            @endif

                                        <!-- End Body -->
                                    </div>
                                    <!-- End Card -->
                                        @if ($order->scheduled)
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center gap-1">
                                                    <div>
                                                        <label class="form-label mb-0 fs-14 font-bold">{{ translate('Schedule Date') }}</label>
                                                        <div class="m-0 font-normal text-title">{{ Helpers::time_date_format($order->schedule_at)  }}</div>
                                                    </div>
                                                    @if ( in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted']))
                                                    <div class="position-relative">
                                                        <div id="customTrigger">
                                                            <i class="tio-calendar-month -absolute-on-right text-gray1"></i>
                                                        </div>
                                                        <input id="hiddenInput" type="text" value="{{ \Carbon\Carbon::parse($order->schedule_at)->format('m/d/Y h:i A') }}" class="border-0 p-0 bg-transparent position-absolute right-0 bottom-0"  name="schedule_at" autocomplete="off">
                                                    </div>

                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            @if ($order->order_type == 'dine_in')
                            <div class="col-12">
                                <div class="card mt-2 mb-2">
                                    <div class="card-body pt-3">
                                        <form action="{{ route('admin.order.add_dine_in_table_number', [$order['id']]) }}" method="post">
                                            @method('PUT')
                                            @csrf
                                        <div class="form-group mb-20">
                                            <label class="form-label">{{translate('Table_Number')}}</label>
                                            <div class="d-flex">
                                                <input type="text"  @readonly(in_array($order['order_status'],['failed','delivered','refund_requested','canceled','refunded','refund_request_canceled'])) maxlength="20" value="{{ $order?->OrderReference?->table_number }}"  name="table_number" class="form-control" placeholder="Ex: 10">
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label">{{translate('Token_Number')}}</label>
                                            <div class="d-flex">
                                                <input type="text"  @readonly(in_array($order['order_status'],['failed','delivered','refund_requested','canceled','refunded','refund_request_canceled'])) maxlength="20" value="{{ $order?->OrderReference?->token_number }}" name="token_number" class="form-control" placeholder="Ex: 32">
                                            </div>
                                        </div>
                                        @if (!in_array($order['order_status'],['failed','delivered','refund_requested','canceled','refunded','refund_request_canceled']))
                                        <div class="w-100 text-center mt-4">
                                            <button type="submit" class="btn w-100 btn-primary font-regular">{{ translate('messages.Save') }}
                                            </button>
                                        </div>
                                        @endif
                                    </form>
                                    </div>
                                </div>
                                </div>
                            @endif

                            @if ($order->delivery_man && $order->delivery_man->type == 'zone_wise')
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body pt-2">
                                            <div class="mb-3 mt-2 d-flex justify-content-between align-items-center">
                                                <h5 class="card-title">
                                                    <span class="card-header-icon">
                                                        <i class="tio-user"></i>
                                                    </span>
                                                    <span>
                                                        {{translate('deliveryman')}}
                                                    </span>
                                                </h5>
                                                @if ( !in_array($order['order_status'], ['delivered','refund_requested','canceled','refunded','refund_request_canceled']) &&  !in_array($order['order_type'],['dine_in','take_away']) &&
                                                (isset($order->restaurant) && ($order->restaurant->restaurant_model == 'commission'
                                                        && !$order->restaurant->self_delivery_system ) ||  ($order->restaurant->restaurant_model == 'subscription'
                                                            && isset($order->restaurant->restaurant_sub) && $order->restaurant->restaurant_sub->self_delivery == 0)))

                                                    <span class="ml-auto text--primary position-relative p-2 cursor-pointer" data-toggle="modal" data-target="#myModal">
                                                        {{ translate('messages.change') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="w-100 text-right initial-39-4">
                                            </div>
                                            <a class="media align-items-center  deco-none customer--information-single"
                                                href="{{ route('admin.delivery-man.preview', [$order->delivery_man['id']]) }}">
                                                <div class="avatar avatar-circle">
                                                    <img class="avatar-img w-75px onerror-image"
                                                         src="{{ $order?->delivery_man?->image_full_url ?? dynamicAsset('public/assets/admin/img/160x160/img3.png') }}"
                                                         alt="Image Description"
                                                         data-onerror-image="{{ dynamicAsset('public/assets/admin/img/160x160/img3.png') }}">
                                                </div>
                                                <div class="media-body">
                                                    <strong class="d-block text--title">
                                                        {{ $order->delivery_man['f_name'] . ' ' . $order->delivery_man['l_name'] }}
                                                    </strong>
                                                    <span>
                                                        <strong class="text--title font-semibold">
                                                            {{ $order->delivery_man->orders_count }}
                                                        </strong>
                                                        {{ translate('messages.orders_delivered') }}
                                                    </span>
                                                    <span class="text--title font-semibold d-block">
                                                        <i class="tio-call-talking-quiet"></i> {{ $order->delivery_man['phone'] }}
                                                    </span>
                                                    <span class="text--title text-lowercase">
                                                        <i class="tio-email"></i> {{ $order->delivery_man['email'] }}
                                                    </span>
                                                </div>
                                            </a>
                                            <hr>
                                            @php($address = $order->dm_last_location)
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5>{{ translate('messages.last_location') }}</h5>
                                            </div>
                                            @if (isset($address))
                                                <span class="d-block">
                                                    <a target="_blank"
                                                        href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                                        <i class="tio-poi"></i> {{ $address['location'] }}<br>
                                                    </a>
                                                </span>
                                            @else
                                                <span class="d-block text-lowercase qcont">
                                                    {{ translate('messages.location_not_found') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                        <div class="col-12 mt-2">
                        <!-- Customer Card -->
                            <div class="card">
                                <div class="card-body pt-3">
                                    <!-- Header -->
                                    <h5 class="card-title mb-3">
                                        <span class="card-header-icon">
                                            <i class="tio-user"></i>
                                        </span>
                                        <span>{{ translate('messages.customer_info') }}</span>
                                    </h5>
                                    <!-- End Header -->
                                        @if ($order?->customer && $order->is_guest == 0)
                                            <a class="media align-items-center deco-none customer--information-single"
                                                href="{{ route('admin.customer.view', [$order?->customer['id']]) }}">
                                                <div class="avatar avatar-circle">
                                                    <img class="avatar-img onerror-image"
                                                         src="{{ $order?->customer?->image_full_url ?? dynamicAsset('public/assets/admin/img/160x160/img1.png') }}"
                                                         alt="Image Description"
                                                         data-onerror-image="{{ dynamicAsset('public/assets/admin/img/160x160/img1.png') }}">

                                                </div>
                                                <div class="media-body">
                                                    <span
                                                        class="fz--14px text--title font-semibold text-hover-primary d-block">
                                                        {{ $order?->customer['f_name'] . ' ' . $order?->customer['l_name'] }}
                                                    </span>
                                                    <span>
                                                        <strong class="text--title font-semibold">{{ $order?->customer->orders_count }}</strong>
                                                        {{ translate('messages.orders') }}
                                                    </span>
                                                    <span class="text--title font-semibold d-block">
                                                        <i class="tio-call-talking-quiet"></i> {{ $order?->customer['phone'] }}
                                                    </span>
                                                    <span class="text--title">
                                                        <i class="tio-email"></i> {{ $order?->customer['email'] }}
                                                    </span>
                                                </div>

                                            </a>
                                        @elseif($order->is_guest)
                                            <span class="badge badge-soft-success py-2 d-block qcont">
                                                {{ translate('Guest_user') }}
                                            </span>

                                        @else
                                            {{translate('messages.customer_not_found')}}
                                        @endif
                                        @if ($order->delivery_address)
                                            <div class="pt-2"></div>
                                                <hr>
                                                @php($address = json_decode($order->delivery_address, true))
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title">
                                                        <span class="card-header-icon">
                                                            <i class="tio-user"></i>
                                                        </span>
                                                        <span>{{ $order->order_type == 'dine_in' ? translate('Order_Info') : translate('delivery_info') }}</span>
                                                    </h5>
                                                    @if (isset($address) && isset($order->restaurant) && !in_array($order['order_type'],['dine_in','take_away'])  && in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted']) )
                                                        <a class="link" data-toggle="modal" data-target="#shipping-address-modal" href="javascript:"><i class="tio-edit"></i></a>
                                                    @endif
                                                </div>
                                                @if (isset($address))
                                                    <span class="delivery--information-single mt-3">
                                                        <span class="name">{{ translate('name') }}</span>
                                                        <span class="info">{{ $address['contact_person_name'] }}</span>
                                                        <span class="name">{{ translate('contact') }}</span>
                                                        <a class="deco-none info" href="tel:{{ $address['contact_person_number'] }}">
                                                            <i class="tio-call-talking-quiet"></i>
                                                            {{ $address['contact_person_number'] }}</a>


                                                            @if ( !in_array($order->order_type, ['dine_in', 'take_away']))
                                                                <span class="name">{{ translate('Road') }} #</span>
                                                                <span class="info">{{ isset($address['road']) ? $address['road'] : '' }}</span>
                                                                <span class="name">{{ translate('House') }} #</span>
                                                                <span class="info">
                                                                    {{ isset($address['house']) ? $address['house'] : '' }}
                                                                </span>
                                                                <span class="name">{{ translate('Floor') }}</span>
                                                                <span class="info">{{ isset($address['floor']) ? $address['floor'] : '' }}</span>

                                                                @if (isset($address['address']))
                                                                    @if (empty($address['longitude']) && empty($address['latitude']) && isset($address['latitude']) && isset($address['longitude']))
                                                                        <div class="mt-2 d-flex w-100">
                                                                            <a target="_blank"
                                                                                href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                                                                <span><i class="tio-poi text--title"></i></span>
                                                                                <span class="info pl-2">{{ $address['address'] }}</span>
                                                                            </a>
                                                                        </div>
                                                                    @else
                                                                        <div class="mt-2 d-flex w-100">
                                                                            <span><i class="tio-poi text--title"></i></span>
                                                                            <span class="info pl-2">{{ $address['address'] }}</span>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            @endif
                                                    </span>
                                                @endif
                                        @endif
                                    </div>
                                <!-- End Body -->
                            </div>
                        </div>

                        @if ($order->order_type != 'dine_in')
                        <!-- order proof -->
                        <div  class="col-12 mt-2">
                            <div class="card ">
                                <div class="card-header d-flex border-0 text-center justify-content-between pb-0">
                                    <h4 class="m-0">
                                        <span class="card-header-icon">
                                            <i class="tio-user"></i>
                                        </span>
                                        <span>{{ translate('messages.Delivery_Proof') }}</span>
                                    </h4>

                                        <button class="btn align-items-center btn-primary btn-sm" data-toggle="modal" data-target=".order-proof-modal">
                                                            {{ translate('messages.add') }} </button>
                                </div>
                                @php($data = isset($order->order_proof) ? json_decode($order->order_proof, true) : 0)

                                <div class="card-body pt-2">
                                    @if ($data)
                                        <label class="input-label"for="order_proof">{{ translate('messages.image') }} : </label>
                                        <div class="row g-3">
                                                @foreach ($data as $key => $img)
                                                @php($img = is_array($img)?$img:['img'=>$img,'storage'=>'public'])
                                                    <div class="col-3">
                                                            <img class="img__aspect-1 rounded border w-100" data-toggle="modal"  data-target="#imagemodal{{ $key }}"
                                                            src="{{Helpers::get_full_url('order',$img['img'],$img['storage']) }}"
                                                            alt="image">

                                                    </div>
                                                    <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1"
                                                        role="dialog" aria-labelledby="order_proof_{{ $key }}"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h4 class="modal-title"
                                                                        id="order_proof_{{ $key }}">
                                                                        {{ translate('order_proof_image') }}</h4>
                                                                    <button type="button" class="close"
                                                                        data-dismiss="modal"><span
                                                                            aria-hidden="true">&times;</span><span
                                                                            class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <img src="{{Helpers::get_full_url('order',$img['img'],$img['storage']) }}"
                                                                        class="initial--22 w-100">
                                                                </div>
                                                                @php($storage = $img['storage'] ?? 'public')
                                                                @php($file = $storage == 's3'?base64_encode('order/' . $img['img']):base64_encode('public/order/' . $img['img']))
                                                                <div class="modal-footer">
                                                                    <a class="btn btn-primary"
                                                                       href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                            class="tio-download"></i>
                                                                        {{ translate('messages.download') }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-12 mt-2">
                            <!-- End Card -->
                            @if ($order->restaurant)
                                <!-- Restaurant Card -->
                                <div class="card">
                                    <!-- Body -->
                                    <div class="card-body">
                                        <!-- Header -->
                                        <h5 class="card-title mb-3">
                                            <span class="card-header-icon">
                                                <i class="tio-shop"></i>
                                            </span>
                                            <span>{{ translate('messages.restaurant_info') }}</span>
                                        </h5>
                                        <!-- End Header -->
                                        <a class="media align-items-center deco-none resturant--information-single"
                                            href="{{ route('admin.restaurant.view', [$order->restaurant['id']]) }}">
                                            <div class="avatar avatar-circle">
                                                    <img class="avatar-img w-75px"
                                                    src="{{ $order?->restaurant?->logo_full_url ?? dynamicAsset('public/assets/admin/img/100x100/restaurant-default-image.png') }}"
                                                    alt="image">

                                            </div>
                                            <div class="media-body">
                                                <span class="text-body text-hover-primary text-break"></span>
                                                <span></span>


                                                <span class="fz--14px text--title font-semibold text-hover-primary d-block">
                                                    {{ $order->restaurant->name }}
                                                </span>
                                                <span>
                                                    <strong class="text--title font-semibold">
                                                        {{ $order->restaurant->orders_count }}
                                                    </strong>
                                                    {{ translate('messages.orders_served') }}
                                                </span>
                                                <span class="text--title font-semibold d-block">
                                                    <i class="tio-call-talking-quiet"></i> {{ $order->restaurant['phone'] }}
                                                </span>
                                                <span class="text--title">
                                                    <i class="tio-poi"></i> {{ $order->restaurant['address'] }}
                                                </span>
                                            </div>
                                        </a>
                                    </div>
                                    <!-- End Body -->
                                </div>
                            @endif
                            <!-- End Card -->
                        </div>
                </div>
            </div>
        </div>
        <!-- End Row -->
    </div>

    <!-- Modal -->
    <div class="modal fade" id="refund_cancelation_note" tabindex="-1" role="dialog" aria-labelledby="refund_cancelation_note_l" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refund_cancelation_note_l">{{ translate('messages.Add_Order_Rejection_Note') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.refund.order_refund_rejection') }}" method="post">
                    @method('PUT')
                    @csrf
                    <div class="modal-body">
                            <input type="hidden" name="order_id" value="{{ $order->id }}" >
                    <input type="text" class="form-control" name="admin_note" value="{{ old('admin_note') }}" placeholder="Fake Order">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('messages.Close') }} </button>
                        <button type="submit" class="btn btn-primary"> {{ translate('messages.Submit') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{ translate('messages.reference_code_add') }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                        aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.order.add-payment-ref-code', [$order['id']]) }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <!-- Input Group -->
                        <div class="form-group">
                            <input type="text" name="transaction_reference" class="form-control"
                                placeholder="{{ translate('messages.Ex:_Code123') }}" required>
                        </div>
                        <!-- End Input Group -->
                        <button class="btn btn-primary">{{ translate('messages.submit') }}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!-- Modal -->
    <div id="shipping-address-modal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalTopCoverTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-top-cover bg-dark text-center">
                    <figure class="position-absolute right-0 bottom-0 left-0 mb-n-1">
                        <svg preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                            viewBox="0 0 1920 100.1">
                            <path fill="#fff" d="M0,0c0,0,934.4,93.4,1920,0v100.1H0L0,0z" />
                        </svg>
                    </figure>

                    <div class="modal-close">
                        <button type="button" class="btn btn-icon btn-sm btn-ghost-light" data-dismiss="modal"
                            aria-label="Close">
                            <svg width="16" height="16" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor"
                                    d="M11.5,9.5l5-5c0.2-0.2,0.2-0.6-0.1-0.9l-1-1c-0.3-0.3-0.7-0.3-0.9-0.1l-5,5l-5-5C4.3,2.3,3.9,2.4,3.6,2.6l-1,1 C2.4,3.9,2.3,4.3,2.5,4.5l5,5l-5,5c-0.2,0.2-0.2,0.6,0.1,0.9l1,1c0.3,0.3,0.7,0.3,0.9,0.1l5-5l5,5c0.2,0.2,0.6,0.2,0.9-0.1l1-1 c0.3-0.3,0.3-0.7,0.1-0.9L11.5,9.5z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- End Header -->

                <div class="modal-top-cover-icon">
                    <span class="icon icon-lg icon-light icon-circle icon-centered shadow-soft">
                        <i class="tio-location-search"></i>
                    </span>
                </div>

                @if (isset($address))
                    <form action="{{ route('admin.order.update-shipping', [$order['id']]) }}" method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.type') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address_type"
                                        value="{{ isset($address['address_type']) ? $address['address_type'] : '' }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.contact') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_number"
                                        value="{{ $address['contact_person_number'] }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.name') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_name"
                                        value="{{ $address['contact_person_name'] }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('House') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="house"
                                        value="{{ isset($address['house']) ? $address['house'] : '' }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('Floor') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="floor"
                                        value="{{ isset($address['floor']) ? $address['floor'] : '' }}" >
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('Road') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="road"
                                        value="{{ isset($address['road']) ? $address['road'] : '' }}" >
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('address') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address" id="address"
                                        value="{{ $address['address'] }}">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('latitude') }}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" readonly name="latitude" id="latitude"
                                        value="{{ $address['latitude'] }}">
                                </div>
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.longitude') }}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" readonly name="longitude" id="longitude"
                                        value="{{ $address['longitude'] }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <input id="pac-input" class="controls rounded initial-8"
                                title="{{ translate('messages.search_your_location_here') }}" type="text"
                                placeholder="{{ translate('messages.search_here') }}" />
                            <div class="mb-2 h-200px" id="map"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white"
                                data-dismiss="modal">{{ translate('messages.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('messages.save_changes') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Dm assign Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header align-items-start">
                    <div>
                        <h4 class="modal-title" id="myModalLabel">{{ $selected_delivery_man != [] ?  translate('messages.Change_Delivery_Men') :  translate('messages.Available_Delivery_Men') }}</h4>
                        <p class="fs-12 text--semititle"  >{{  count($deliveryMen) > 0  ?  count($deliveryMen) - ($order->delivery_man_id ? 1 : 0) : 0 }}   {{  translate('Delivery_Man_Available') }}</p>

                    </div>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="row">
                        <div class="col-md-5 d-flex  justify-content-center">
                            <ul class="list-group overflow-auto max-height-400">
                                @if ($selected_delivery_man != [] )

                                <li class="list-group-item">
                                    <div class="d-flex align-items-center gap-2 justify-content-between">
                                            <div class="dm_list_selected media gap-2" data-id="{{ $selected_delivery_man['id'] }}">
                                                <img class="avatar avatar-60 rounded-10"
                                                src="{{ $selected_delivery_man['image_full_url'] ?? dynamicAsset('public/assets/admin/img/160x160/img3.png') }}"
                                                     alt="Image Description"
                                                     data-onerror-image="{{ dynamicAsset('public/assets/admin/img/160x160/img1.png') }}">
                                                    <div class="media-body d-flex gap-1 flex-column">
                                                        <h6 class="mb-1">  {{ $selected_delivery_man['name'] }}</h6>
                                                        <div class="fs-12 text-muted">
                                                            {{ translate('Active_Orders') }} : {{ $selected_delivery_man['current_orders'] }}
                                                        </div>

                                                        <div class="fs-12 text-muted">
                                                            {{ $selected_delivery_man['distance']}} {{  translate('away_from_restaurant') }}
                                                        </div>
                                                    </div>
                                            </div>
                                            <span class="badge __badge badge-success __badge-abs">
                                                {{  translate('Currently_Assigned') }}
                                            </span>
                                    </div>
                                </li>
                                @endif



                                @forelse ($deliveryMen as $dm)
                                    @if ($dm['id'] != $order->delivery_man_id )
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center gap-2 justify-content-between">
                                                    <div class="dm_list media gap-2" data-id="{{ $dm['id'] }}">
                                                        <img class="avatar avatar-60 rounded-10 onerror-image"
                                                             src="{{ $dm['image_full_url'] ?? dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                             alt="{{ $dm['name'] }}"
                                                             data-onerror-image="{{ dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}">
                                                            <div class="media-body d-flex gap-1 flex-column">
                                                                <h6 class="mb-1">  {{ $dm['name'] }}</h6>
                                                                <div class="fs-12 text-muted">
                                                                    {{ translate('Active_Orders') }} : {{ $dm['current_orders'] }}
                                                                </div>

                                                                <div class="fs-12 text-muted">
                                                                    {{ $dm['distance']}} {{  translate('away_from_restaurant') }}
                                                                </div>
                                                            </div>
                                                    </div>



                                                    <?php
                                                    $dm_over_flow= false;
                                                    $cash_in_hand = $dm['wallet']?->collected_cash ?? 0;
                                                    $dm_max_cash=Helpers::get_business_data('dm_max_cash_in_hand') ?? 0;

                                                    if($order->payment_method == "cash_on_delivery" && (($cash_in_hand+$order->order_amount) >= $dm_max_cash)){
                                                       $dm_over_flow = true;
                                                    }
                                                    ?>
                                                    @if ($order->delivery_man_id )

                                                        <a class="btn  btn-xs float-right  {{ $dm_over_flow == true ? 'btn-secondary disableDM'  : 'btn-primary add-delivery-man'}}"
                                                        data-id="{{ $dm['id'] }}">{{ translate('messages.Reassign') }}</a>
                                                    @else
                                                        <a class="btn btn-xs float-right {{ $dm_over_flow == true ? 'btn-secondary disableDM'  : 'btn-primary add-delivery-man'}}"
                                                        data-id="{{ $dm['id'] }}">{{ translate('messages.assign') }}</a>
                                                    @endif
                                            </div>
                                        </li>
                                    @endif
                                    @empty
                                    <div class="text-center">
                                        <img src="{{ dynamicAsset('public/assets/admin/img/dmimage.png') }}" alt="image">

                                        <div class="text-center">
                                            <p>{{translate('Currently_no_deliveryman_available.')}}
                                                <br>
                                                <span class="fz-12px">
                                                    {{ translate('Please_check_after_some_times_or_reload_the_page.') }}
                                                </span>
                                            </p>

                                        </div>
                                    </div>
                                @endforelse
                            </ul>
                        </div>
                        <div class="col-md-7 modal_body_map">
                            <div class="location-map" id="dmassign-map">
                                <div id="map_canvas"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Show locations on map Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="locationModalLabel">{{ translate('messages.location_data') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 modal_body_map">
                            <div class="location-map" id="location-map">
                                <div id="location_map_canvas"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <div class="modal quick_modal_init fade" id="quick-view" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" id="quick-view-modal">

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade order-proof-modal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{ translate('messages.add_delivery_proof') }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                        aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.order.add-order-proof', [$order['id']]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <!-- Input Group -->
                        <div class="flex-grow-1 mx-auto">
                            <div class="d-flex flex-wrap __gap-12px __new-coba" id="coba">
                                @php($proof = isset($order->order_proof) ? json_decode($order->order_proof, true) : 0)
                                @if ($proof)

                                @foreach ($proof as $key => $photo)
                                @php($photo =  is_string($photo) ? ['img' => $photo, 'storage' => 'public'] :  $photo )
                                            <div class="spartan_item_wrapper min-w-100px max-w-100px">
                                                <img class="img--square"
                                                    src="{{ $order->order_proof_full_url[$key] }}"
                                                    alt="order image">
                                                <a href="{{ route('admin.order.remove-proof-image', ['id' => $order['id'], 'name' => $photo['img']]) }}"
                                                    class="spartan_remove_row"><i class="tio-add-to-trash"></i></a>
                                            </div>
                                        @endforeach
                                @endif
                            </div>
                        </div>
                        <!-- End Input Group -->
                        <div class="text-right mt-2">
                            <button class="btn btn--primary">{{ translate('messages.submit') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->

    @if ($order?->offline_payments)
        <div class="modal fade" id="verifyViewModal" tabindex="-1" aria-labelledby="verifyViewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-end  border-0">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                <div class="modal-body">
                <div class="d-flex align-items-center flex-column gap-3 text-center">
                    <h2>{{translate('Payment_Verification')}}
                        @if ($order?->offline_payments->status == 'verified')
                            <span class="badge badge-soft-success mt-3 mb-3">{{ translate('messages.verified') }}</span>
                        @endif
                    </h2>
                    <p class="text-danger mb-2 mt-2">{{ translate('Please_Check_&_Verify_the_payment_information_weather_it_is_correct_or_not_before_confirm_the_order.') }}</p>
                </div>

                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-sm-6 col-xl-4">
                                <h4 class="mb-3">{{ translate('messages.customer_information') }}</h4>
                                <div class="d-flex flex-column gap-2">
                                    @if($order->is_guest)
                                    @php($customer_details = json_decode($order['delivery_address'],true))

                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{translate('Name')}}</span>:
                                        <span class="text-dark"> {{$customer_details['contact_person_name']}}</span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{translate('Phone')}}</span>:
                                        <span class="text-dark">  {{$customer_details['contact_person_number']}}</span>
                                    </div>

                                    @elseif($order?->customer)
                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{translate('Name')}}</span>:
                                        <span class="text-dark"> <a class="text-body text-capitalize" href="{{route('admin.customer.view',[$order['user_id']])}}"> {{$order?->customer['f_name'].' '.$order?->customer['l_name']}}  </a>  </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{translate('Phone')}}</span>:
                                        <span class="text-dark">{{$order?->customer['phone']}}  </span>
                                    </div>

                                    @else
                                        <label class="badge badge-danger">{{translate('messages.invalid_customer_data')}}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <h4 class="mb-3">{{ translate('messages.Order_Information') }}</h4>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{translate('Order_ID')}}</span>:
                                        <span class="text-dark"> {{$order->id}}</span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{translate('Order_Time')}}</span>:
                                        <span class="text-dark"> {{ Helpers::time_date_format($order->created_at)  }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>


                                <div class="mt-5">
                                    <h4 class="mb-3">{{ translate('messages.Payment_Information') }}</h4>
                                    <div class="row g-3">
                                        @foreach (json_decode($order->offline_payments->payment_info) as $key=>$item)
                                            @if ($key != 'method_id')
                                            <div class="col-sm-6  col-lg-5">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="w-sm-25"> {{translate($key)}}</span>:
                                                    <span class="text-dark text-break">{{ $item }}</span>
                                                </div>
                                            </div>
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="d-flex flex-column gap-2 mt-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>{{translate('Customer_Note')}}</span>:
                                            <span class="text-dark text-break">{{$order->offline_payments?->customer_note ?? translate('messages.N/A')}} </span>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        @if ($order?->offline_payments->status != 'verified')
                        <div class="btn--container justify-content-end mt-3">
                            @if ($order?->offline_payments->status != 'denied')
                            <button type="button" class="btn btn--danger btn-outline-danger offline_payment_cancelation_note" data-toggle="modal" data-target="#offline_payment_cancelation_note" data-id="{{ $order['id'] }}" class="btn btn--reset">{{translate('Payment_Didn’t_Receive')}}</button>
                            @elseif ($order?->offline_payments->status == 'denied')
                            <button type="button" class="btn btn-danger btn-outline-danger mb-2 cancelled_status">{{translate('Cancel_Order')}}</button>
                                <button type="button" data-url="{{ route('admin.order.offline_payment', [ 'id' => $order['id'], 'verify' => 'switched_to_cod', ]) }}" data-message="{{ translate('messages.Make_the_payment_verified_for_this_order') }}"  class="btn btn-primary btn-outline-primary mb-2 route-alert">{{translate('Continue_with_COD')}}</button>
                            @endif
                            <button type="button" data-url="{{ route('admin.order.offline_payment', [ 'id' => $order['id'], 'verify' => 'yes', ]) }}" data-message="{{ translate('messages.Make_the_payment_verified_for_this_order') }}" class="btn btn-primary mb-2 route-alert">{{translate('Yes,_Payment_Received')}}</button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
                <!-- Modal -->
        <div class="modal fade" id="offline_payment_cancelation_note" tabindex="-1" role="dialog"
            aria-labelledby="offline_payment_cancelation_note_l" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="offline_payment_cancelation_note_l">{{ translate('messages.Add_Offline_Payment_Rejection_Note') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.order.offline_payment') }}" method="get">
                            <input type="hidden" name="id" value="{{ $order->id }}">
                            <input type="text" required class="form-control" name="note" value="{{ old('note') }}"
                                placeholder="{{ translate('transaction_id_mismatched') }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{  translate('close') }}</button>
                        <button type="submit" class="btn btn--danger btn-outline-danger">{{ translate('messages.Confirm_Rejection') }} </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Order Edit -->
    <div id="offcanvas__order_edit" class="custom-offcanvas d-flex flex-column justify-content-between" style="--offcanvas-width: 750px">
        <div>
            <form action="{{ route('admin.order.update', [$order['id']]) }}" method="post">
                @csrf
            <div class="custom-offcanvas-header d-flex justify-content-between align-items-center">
                <div class="px-3 py-3 d-flex justify-content-between w-100">
                    <div>
                        <h2 class="mb-1">{{ translate('Edit Item') }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-30">
                            <h3 class="page-header-title d-flex align-items-center gap-2">
                                <span class="font--max-sm fs-14">{{ translate('Order') }} #{{ $order['id'] }}</span>
                                <span class="badge badge-soft-info font-regular m-0">{{ translate($order['order_status']) }}</span>
                            </h3>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-14 font-regular d-block text-dark">{{ translate('Order_Placed') }} :</span>
                                <span class="fs-14 font-semibold d-block text-dark"> {{ Helpers::time_date_format($order->created_at)  }}</span>
                            </div>
                        </div>
                    </div>
                    <button type="button"
                        class="btn-close w-25px h-25px border rounded-circle d-center bg--secondary location-reload offcanvas-close fz-15px p-0"
                        aria-label="Close">&times;
                    </button>
                </div>
            </div>
            <div class="custom-offcanvas-body p-20">
                <div class="mb-20 position-relative edit-search-form">
                    <div class="form-control position-relative bg-white d-flex align-items-center gap-2">
                        <i class="tio-search"></i>
                        <input id="food_search" data-restaurant_id="{{ $order['restaurant_id'] }}" data-url="{{ route('admin.order.getSearchedFoods') }}" type="search" name="search" class="h-100 fs-12 bg-transparent w-100 border-0 rounded-0"  placeholder="Search by food name" autocomplete="off">
                        <!--- After Search -->
                       <div id="food_search_result"> </div>
                    </div>
                </div>


                <div id ="data-view">  </div>

            </div>
            <div class="offcanvas-footer position-absolute bottom-0 start-0 w-100 bg-white p-3 d-flex align-items-center justify-content-end gap-3">
                <button type="button"   class="btn min-w-120 btn--reset location-reload reset">{{ translate('Cancel') }}</button>
                <button type="submit" class="btn min-w-120 btn--primary">{{ translate('Update Cart') }}</button>
            </div>
            </form>
        </div>
    </div>
    <div id="offcanvasOverlay_fixed" class="offcanvasOverlay_fixed"></div>
    
    
    <!-- History Log -->
     <div id="offcanvas__history_log" class="custom-offcanvas d-flex flex-column justify-content-between" style="--offcanvas-width: 570px">
        <div>
            <div class="custom-offcanvas-header d-flex justify-content-between align-items-center">
                <div class="px-3 py-3 d-flex justify-content-between w-100">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="mb-1 fs-18 font-medium">{{ translate('Edit History Log') }}</h2>
                        <h3 class="page-header-title d-flex align-items-center gap-2">
                            <span class="font--max-sm fs-14 font-normal fs-14">(# {{ $order['id'] }})</span>
                        </h3>
                    </div>
                    <button type="button"
                        class="btn-close w-25px h-25px border rounded-circle d-center bg--secondary offcanvas-close fz-15px p-0"
                        aria-label="Close">&times;
                    </button>
                </div>
            </div>
            <div class="custom-offcanvas-body p-20">
                <div class="p-xl-0">
                    <div class="table-responsive pt-0 mb-20">
                        <div class="p-1">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                <thead class="border-0 initial-94">
                                    <tr>
                                        <th class="border-0">{{ translate('Sl') }}</th>
                                        <th class="border-0">{{ translate('Date & time') }}</th>
                                        <th class="border-0 ">{{ translate('Remark') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->orderEditLogs as $key => $log)
                                    <tr>
                                        <td>
                                            <div>
                                                {{ $key + 1 }}
                                            </div>
                                        </td>
                                        <td class="fs-14">
                                            <span class="d-block text-dark">{{ Helpers::date_format($log->created_at) }} </span>
                                            <span class="text-muted">{{ Helpers::time_format($log->created_at) }}</span>
                                        </td>
                                        <td class="fs-14">
                                           {{ translate($log->log) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="offcanvasOverlay" class="offcanvas-overlay"></div>





    <div class="modal fade" id="edit-order">
        <div class="modal-dialog modal-dialog-centered status-warning-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
                <div class="modal-body pb-5 pt-0">
                    <div class="max-349 mx-auto mb-20">
                        <div>
                            <div class="text-center">
                                <img src="{{dynamicAsset('public/assets/admin/img/fi_10308693.png')}}" alt="" class="mb-20">
                                <h5 class="modal-title text-center text-capitalize">{{ translate('Are You sure you want to edit this order?') }}</h5>
                            </div>
                            <div class="text-center">
                                <p>{{ translate('If you edit this order, some product details will be updated, which may affect the total price.') }}</p>
                            </div>
                        </div>
                        <div class="btn--container justify-content-center">
                            <button id="reset_btn" type="reset"  class="btn btn--secondary min-w-120  " data-dismiss="modal">{{translate('No')}}</button>
                            <button class="btn btn--primary min-w-120 offcanvas-trigger offcanvas-trigger_fixed"  id="edit_order_cart"
                            data-url="{{ route('admin.order.edit',$order->id) }}"

                            data-target="#offcanvas__order_edit" data-dismiss="modal">
                                {{translate("Yes")}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="remove-from-cart">
        <div class="modal-dialog modal-dialog-centered status-warning-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
                <div class="modal-body pb-5 pt-0">
                    <div class="max-349 mx-auto mb-20">
                        <div>
                            <div class="text-center">
                                <img src="{{dynamicAsset('public/assets/admin/img/fi_10308693.png')}}" alt="" class="mb-20">
                                <h5 class="modal-title text-center text-capitalize">{{ translate('Are you sure you want to delete this Food?') }}</h5>
                            </div>
                            <div class="text-center">
                                <p>{{ translate('If you delete this food item, it will be removed from the list. You’ll need to add it again by searching in the food list.') }}</p>
                            </div>
                        </div>

                        <input type="hidden" name="delete_cart_id" id="delete_cart_id" value="" >
                        <div class="btn--container justify-content-center">
                            <button  class="btn btn--secondary min-w-120" data-dismiss="modal">{{translate('Cancel')}}</button>
                            <button class="btn btn--danger min-w-120 removeFromCart"

                            data-dismiss="modal">
                                {{translate("Delete")}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('script_2')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ Helpers::get_business_data('map_api_key') }}&libraries=places&callback=initMap&v=3.45.8"></script>
    <script src="{{ dynamicAsset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script type="text/javascript" src="{{dynamicAsset('public/assets/admin/js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{dynamicAsset('public/assets/admin/js/daterangepicker.min.js')}}"></script>
    <script>
        $(document).ready(function() {
            $('.offcanvas-trigger_fixed').on('click', function() {
                // Add 'active' class to the overlay
                $('.offcanvasOverlay_fixed').addClass('active');
            });

            // Close functionality
            $('.offcanvas-close').on('click', function() {
                $('.offcanvasOverlay_fixed').removeClass('active');
            });
        });
    </script>
    <script>

        $(document).on('click', '.disable-delete-btn-text', function () {
            toastr.error('{{ translate('You must have at least one food in cart.You can not delete this food.') }}', { CloseButton: true,ProgressBar: true });
        });

        $(document).on('click', '.quick-View', function () {
            let product_id = $(this).data('id');
            $.get({
                url: '{{ route('admin.order.quick-view') }}',
                dataType: 'json',
                data: {
                    product_id: product_id,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        })
        $(document).on('click', '.remove_from_cart_id', function () {
            var cartId = $(this).data('cart_id');
            $('#delete_cart_id').val(cartId);
        });



        $(document).ready(function () {
            let searchTimeout;

            $('#food_search').on('keyup', function () {
                clearTimeout(searchTimeout);

                let query = $(this).val().trim();
                let restaurantId = $(this).data('restaurant_id');
                let url = $(this).data('url');

                searchTimeout = setTimeout(function () {
                    if (query.length > 0) {
                        $.ajax({
                            url: url,
                            method: 'GET',
                            data: {
                                search: query,
                                restaurant_id: restaurantId
                            },
                            beforeSend: function() {
                                $('#food_search_result').empty();

                            },
                            success: function (response) {
                                $('#food_search_result').append(response.view);
                            }
                        });
                    }
                }, 300);
            });


                document.querySelectorAll('[name="search"]').forEach(function (element) {
                element.addEventListener("input", function (event) {
                        if (this.value === "" ) {
                          $('#food_search_result').empty();
                        }
                    });
                });
        });

        $(document).on('click', '#edit_order_cart', function() {
            // $('#content-disable').addClass('disabled');
            fetch_data($(this).data('url'))
        })

        function fetch_data(url) {
            $.ajax({
                url: url,
                type: "get",
                beforeSend: function() {
                    $('#data-view').empty();
                    $('#loading').show()
                },
                success: function(data) {
                    if(data.error === 'food_not_found'){
                        $('.custom-offcanvas').removeClass('open');
                        $('#offcanvasOverlay').removeClass('show');
                        toastr.error('{{ translate('messages.product_deleted') }}', { CloseButton: true,ProgressBar: true });
                    } else {
                        $("#data-view").append(data.view);

                    }
                },
                complete: function() {
                    $('#loading').hide()
                }
            })
        }

        $(function () {
            const $input = $('#hiddenInput');
         $input.daterangepicker({
            singleDatePicker: true,
            timePicker: true,
            timePicker24Hour: false,
            timePickerSeconds: true,
            autoUpdateInput: false,
            minDate: moment(),
            locale: { format: 'MM/DD/YYYY hh:mm A' },
            parentEl: 'body'
        });

            $input.on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY hh:mm A'));
                $.post('{{ route("admin.order.updateSchedule") }}', {
                    _token: '{{ csrf_token() }}',
                    order_id: '{{ $order->id }}',
                    date: picker.startDate.format('YYYY-MM-DD HH:mm:ss')
                }, function (response) {
                    if (response.status === 'success') {
                         toastr.success(response.message);
                          setTimeout(function () {
                            location.reload();
                        }, 800);
                    } else {
                       toastr.error(response.message);
                    }
                });
            });
            $('#customTrigger').on('click', function () {
                $input.data('daterangepicker').show();
            });


            $input.on('show.daterangepicker', function (e, picker) {
                picker.container.addClass('date__custom_width');
            });
        });

         $(document).ready(function () {
            let $stickyDiv = $("#stickyDiv");

            function checkSticky() {
                let rect = $stickyDiv[0].getBoundingClientRect();

                if (rect.bottom >= window.innerHeight && rect.top < window.innerHeight) {
                    $stickyDiv.addClass("total_price_sticky_wrapper");
                } else {
                    $stickyDiv.removeClass("total_price_sticky_wrapper");
                }
            }

            $(window).on("scroll resize", checkSticky);

            if ({{ $order->bring_change_amount > 0 ? 'true' : 'false' }}){
                checkSticky();
            }
        });
    </script>

    <script>
        $(document).on('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                return false;
            }
        });

        $(document).on('click', '.addon-quantity-input-toggle', function (event) {
            let cb = $(event.target);
            if (cb.is(":checked")) {
                cb.siblings('.addon-quantity-input').css({
                    'visibility': 'visible'
                });
            } else {
                cb.siblings('.addon-quantity-input').css({
                    'visibility': 'hidden'
                });
            }
        })
       $(document).on('click', '.quick_view_cart_item', function (event) {
            quick_view_cart_item($(this).data('id'), $(this).data('cart_id'));
        })


        function quick_view_cart_item(key, cart_id) {
            $.get({
                url: '{{ route('admin.order.quick-view-cart-item') }}',
                dataType: 'json',
                data: {
                    key: key,
                    cart_id: cart_id,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        }


        function cartQuantityInitialize() {
            $('.btn-number').click(function(e) {
                e.preventDefault();

                let fieldName = $(this).attr('data-field');
                let type = $(this).attr('data-type');
                let input = $("input[name='" + fieldName + "']");
                let currentVal = parseInt(input.val());

                if (!isNaN(currentVal)) {
                    if (type == 'minus') {

                        if (currentVal > input.attr('min')) {
                            input.val(currentVal - 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('min')) {
                            $(this).attr('disabled', true);
                        }

                    } else if (type == 'plus') {

                        if (currentVal < input.attr('max')) {
                            input.val(currentVal + 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('max')) {
                            $(this).attr('disabled', true);
                        }

                    }
                } else {
                    input.val(0);
                }
            });

            $('.input-number').focusin(function() {
                $(this).data('oldValue', $(this).val());
            });

            $('.input-number').change(function() {

                minValue = parseInt($(this).attr('min'));
                maxValue = parseInt($(this).attr('max'));
                valueCurrent = parseInt($(this).val());

                let name = $(this).attr('name');
                if (valueCurrent >= minValue) {
                    $(".btn-number[data-type='minus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, the minimum value was reached'
                    });
                    $(this).val($(this).data('oldValue'));
                }
                if (valueCurrent <= maxValue) {
                    $(".btn-number[data-type='plus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, stock limit exceeded.'
                    });
                    $(this).val($(this).data('oldValue'));
                }
            });
            $(".input-number").keydown(function(e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
                    // Allow: Ctrl+A
                    (e.keyCode == 65 && e.ctrlKey === true) ||
                    // Allow: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    // let it happen, don't do anything
                    return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        }

        function getVariantPrice() {
            if (typeof getCheckedInputs === 'function') {
                getCheckedInputs();
            }
            if ($('#add-to-cart-form input[name=quantity]').val() > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
                $.ajax({
                    type: "POST",
                    url: '{{ route('admin.pos.variant_price') }}',
                    data: $('#add-to-cart-form').serializeArray(),
                    success: function(data) {
                        if (data.error === 'quantity_error') {
                            toastr.error(data.message);
                        }
                        else if(data.error === 'stock_out'){
                            toastr.warning(data.message);
                            if(data.type == 'addon'){
                                $('#addon_quantity_button'+data.id).attr("disabled", true);
                                $('#addon_quantity_input'+data.id).val(data.current_stock);
                            }

                            else{
                                $('#quantity_increase_button').attr("disabled", true);
                                $('#add_new_product_quantity').val(data.current_stock);
                            }
                            getVariantPrice();
                        }

                        else {
                            $('#add-to-cart-form #chosen_price_div').removeClass('d-none');
                            $('#add-to-cart-form #chosen_price_div #chosen_price').html(data.price);
                            $('.add-To-Cart').removeAttr("disabled");
                            $('.increase-button').removeAttr("disabled");
                            $('#quantity_increase_button').removeAttr("disabled");

                        }

                    }
                });
            }
        }


        $(document).on('change', '.update-Quantity', function () {
            handleQuantityChange($(this));
        });


        $(document).on('click', '.decrease-button', function () {
            let addonId = $(this).data('id');
            let addon_quantity_input = $('input[name="addon-quantity' + addonId + '"]');
            let currentValue = parseInt(addon_quantity_input.val(), 10);
            if (currentValue > 1) {
                addon_quantity_input.val(currentValue - 1);
                getVariantPrice();
            }
        });

        $(document).on('click', '.increase-button', function () {
            let addonId = $(this).data('id');
            let addon_quantity_input = $('input[name="addon-quantity' + addonId + '"]');
            let currentValue = parseInt(addon_quantity_input.val(), 10);
            addon_quantity_input.val(currentValue + 1);
            getVariantPrice();
        });



        $(document).on('click', '.decrease-quantity-button', function () {
            let Id = $(this).data('id');
            let quantity_input = $('#update_quantity_' + Id);
            let currentValue = parseInt(quantity_input.val(), 10);
            if (currentValue > parseInt(quantity_input.attr('min'))) {
                quantity_input.val(currentValue - 1);
            }
             quantity_input.trigger('change');
        });


        $(document).on('click', '.increase-quantity-button', function () {
            let Id = $(this).data('id');
            let quantity_input = $('#update_quantity_' + Id);
            let currentValue = parseInt(quantity_input.val(), 10);
            // if (currentValue < parseInt(quantity_input.attr('max'))) {
            // }
            quantity_input.val(currentValue + 1);
            quantity_input.trigger('change');
        });


        function handleQuantityChange(element) {

            let valueCurrent = parseInt(element.val());
            let key = element.data('key');
            let option_ids = element.data('option_ids');
            let variation_options_old = element.data('variation_options_old');
            let addon_price = element.data('addon_price');
            let order_detail_id = element.data('order_detail_id');
            let food_id = element.data('food_id');
            let oldvalue = element.data('value');

                $.post('{{ route('admin.order.getSingleFoodPrice') }}', {
                    _token: '{{ csrf_token() }}',
                    cart_id: key,
                    option_ids: option_ids,
                    food_id: food_id,
                    addon_price: addon_price,
                    variation_options_old: variation_options_old,
                    order_detail_id: order_detail_id,
                    quantity: valueCurrent
                }, function (data) {
                    if (data.data === 'stock_out') {
                        element.val(oldvalue);
                        Swal.fire({
                            icon: 'error',
                            title: "{{ translate('Cart') }}",
                            text: data.message
                        });
                    } else if (data.data === 'maximum_cart_quantity') {
                         element.val(oldvalue);
                         toastr.error(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                    else {
                        element.data('value', valueCurrent);
                         toastr.success(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        $("#data-view").empty().append(data.view);
                    }
                });

        }




        $(document).on('change', '[name="quantity"]', function (event) {
            getVariantPrice();
            if($('#option_ids').val() == ''){
                $(this).attr('max', $(this).data('maximum_cart_quantity'));
            }
        });

        $(document).on('click', '.add-To-Cart', function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });
            let form_id = 'add-to-cart-form';
            $.post({
                url: '{{ route('admin.order.add-to-cart') }}',
                data: $('#' + form_id).serializeArray(),
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    if (data.data == 1) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Cart',
                            text: "{{ translate('messages.product_already_added_in_cart') }}"
                        });
                        return false;
                    } else if (data.data == 0) {
                        $('#food_search_result').empty();
                        $('#quick-view').modal('hide');
                        $("#data-view").empty().append(data.view);
                        toastr.success(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });

                        return false;

                    } else if (data.data === 'stock_out') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cart',
                            text: data.message
                        });
                        return false;
                    }

                    else if (data.data == 'variation_error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cart',
                            text: data.message
                        });
                        return false;
                    }
                    $('.call-when-done').click();

                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        })


        $(document).on('click', '.add-To-Cart-Single', function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });

            let id = $(this).data('id');
            let item_type = $(this).data('item_type');
            let quantity = $(this).data('quantity');

            $.post({
                url: '{{ route('admin.order.add-to-cart') }}',
                data: {
                    id: id,
                    item_type: item_type,
                    quantity: quantity
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    if (data.data == 1) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Cart',
                            text: "{{ translate('messages.product_already_added_in_cart') }}"
                        });
                        return false;
                    } else if (data.data == 0) {
                        $('#food_search_result').empty();
                        $('#quick-view').modal('hide');
                        $("#data-view").empty().append(data.view);
                        toastr.success('{{ translate('messages.product_has_been_added_in_cart') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        return false;
                    } else if (data.data === 'stock_out') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cart',
                            text: data.message
                        });
                        return false;
                    } else if (data.data == 'variation_error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cart',
                            text: data.message
                        });
                        return false;
                    }
                    $('.call-when-done').click();
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        });

        $(document).on('click', '.removeFromCart', function () {
                $.post('{{ route('admin.order.remove-from-cart') }}', {
                        _token: '{{ csrf_token() }}',
                        cart_id:  $('#delete_cart_id').val(),
                        order_id: '{{ $order->id }}'
                    }, function(data) {
                        $('#quick-view').modal('hide');
                        if (data.errors) {
                            for (let i = 0; i < data.errors.length; i++) {
                                toastr.error(data.errors[i].message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                        } else {
                        $('#food_search_result').empty();
                        $('#quick-view').modal('hide');
                        $("#data-view").empty().append(data.view);
                            toastr.success('{{ translate('messages.item_has_been_removed_from_cart') }}', {
                                CloseButton: true,
                                ProgressBar: true
                            });

                        }
                    });
        })


        $('.js-select2-custom').each(function () {
            let select2 = $.HSCore.components.HSSelect2.init($(this));
        });

        $('.add-delivery-man').on('click', function(){
            let id = $(this).data('id');
            addDeliveryMan(id);
        })
        $('.disableDM').on('click', function(){
            toastr.info('{{ translate('messages.delivery_man_max_cash_in_hand_exceeds') }}', {
                                CloseButton: true,
                                ProgressBar: true
                            });

        })
        function addDeliveryMan(id) {
            $.ajax({
                type: "GET",
                url: '{{ url('/') }}/admin/order/add-delivery-man/{{ $order['id'] }}/' + id,
                success: function(data) {

                    toastr.success('{{ translate('Successfully_added') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    location.reload();
                },
                error: function(response) {
                    console.log(response);
                    toastr.error(response.responseJSON.message, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        }

        function last_location_view() {
            toastr.warning('{{ translate('Only_available_when_order_is_out_for_delivery!') }}', {
                CloseButton: true,
                ProgressBar: true
            });
        }




        $('.cancelled_status').on('click', function(){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure?') }}',
                text: '{{ translate('messages.Change_status_to_canceled_?') }}',
                type: 'warning',
                html:
                `   <select class="form-control js-select2-custom mx-1" name="reason" id="reason">
                    <option value=" ">
                            {{  translate('select_cancellation_reason') }}
                        </option>
                    @foreach ($reasons as $r)
                        <option value="{{ $r->reason }}">
                            {{ $r->reason }}
                        </option>
                    @endforeach

                    </select>`,
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true,
                onOpen: function () {
                        $('.js-select2-custom').select2({
                            minimumResultsForSearch: 5,
                            width: '100%',
                            placeholder: "Select Reason",
                            language: "en",
                        });
                    }
            }).then((result) => {
                if (result.value) {
                    // console.log(result);
                    let reason = document.getElementById('reason').value;
                    location.href = '{!! route('admin.order.status', ['id' => $order['id'],'order_status' => 'canceled']) !!}&reason='+reason,'{{ translate('Change_status_to_canceled_?') }}';
                }
            })
        })

        let deliveryMan = <?php echo json_encode($deliveryMen); ?>;
        let map = null;

        let myLatlng = new google.maps.LatLng({{ isset($order->restaurant) ? $order->restaurant->latitude : 0 }},
            {{ isset($order->restaurant) ? $order->restaurant->longitude : 0 }});
        let dmbounds = new google.maps.LatLngBounds(null);
        let locationbounds = new google.maps.LatLngBounds(null);
        let dmMarkers = [];
        dmbounds.extend(myLatlng);
        locationbounds.extend(myLatlng);
        let myOptions = {
            center: myLatlng,
            zoom: 13,
            mapTypeId: google.maps.MapTypeId.ROADMAP,

            panControl: true,
            mapTypeControl: false,
            panControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.LARGE,
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            scaleControl: false,
            streetViewControl: false,
            streetViewControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            }
        };

        function initializeGMap() {

            map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

            let infowindow = new google.maps.InfoWindow();
            let dm_default_image = null;

            @if ($order->restaurant)
                let Restaurantmarker = new google.maps.Marker({
                    position: new google.maps.LatLng(
                        {{ isset($order->restaurant) ? $order->restaurant->latitude : 0 }},
                        {{ isset($order->restaurant) ? $order->restaurant->longitude : 0 }}),
                    map: map,
                    title: "{{ isset($order->restaurant) ? Str::limit($order->restaurant->name, 15, '...') : '' }}",
                    icon: "{{ dynamicAsset('public/assets/admin/img/restaurant_map_1.png') }}"
                });


                google.maps.event.addListener(Restaurantmarker, 'click', (function(Restaurantmarker) {
                    return function() {
                        infowindow.setContent(
                            `<div class='float--left'><img class='js--design-1'
                                src="{{ $order?->restaurant?->logo_full_url ?? dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                    alt='image'></div><div class='text-break float--right p--10px'><b>{{ Str::limit($order->restaurant->name, 15, '...') }}</b><br/> {{ $order->restaurant->address }}</div>`
                        );
                        infowindow.open(map, Restaurantmarker);
                    }
                })(Restaurantmarker));
            @endif
            map.fitBounds(dmbounds);
            for (let i = 0; i < deliveryMan.length; i++) {
                if (deliveryMan[i].lat) {
                    let point = new google.maps.LatLng(deliveryMan[i].lat, deliveryMan[i].lng);
                    dmbounds.extend(point);
                    map.fitBounds(dmbounds);
                    let d_man = "{{ $order?->delivery_man_id ?? 0 }}" ;
                    if ( deliveryMan[i].id ==  d_man){
                        dm_default_image=  "{{ dynamicAsset('public/assets/admin/img/delivery_boy_map_2.png') }}";
                    } else {
                        dm_default_image=  "{{ dynamicAsset('public/assets/admin/img/delivery_boy_map_1.png') }}";
                    }

                    let marker = new google.maps.Marker({
                        position: point,
                        map: map,
                        title: deliveryMan[i].location,
                        icon:  dm_default_image


                    });
                    dmMarkers[deliveryMan[i].id] = marker;
                    google.maps.event.addListener(marker, 'click', (function(marker, i) {
                        return function() {
                            infowindow.setContent(
                                `<div class='float--left'><img class='js--design-1 mt-2' onerror="this.src='{{ dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}'"   src='{{ dynamicStorage('storage/app/public/delivery-man') }}/` +
                                deliveryMan[i].image +
                                "'></div><div class='float--right p--10px'><b>" + deliveryMan[i]
                                .name + "</b><br/> <div> {{ translate('messages.Active_Orders') }} :" + deliveryMan[i].current_orders + "</div> </b>" + deliveryMan[i].location + "</div>");
                            infowindow.open(map, marker);
                        }
                    })(marker, i));
                }

            };
        }

        function initMap() {
            let zonePolygon = null;
            //get current location block
            let infoWindow = new google.maps.InfoWindow();
            // Try HTML5 geolocation.
            if(!document.getElementById("map")){
                return true;
            }
            let map = new google.maps.Map(document.getElementById("map"), myOptions);

            @if (isset($address) && isset($address['latitude']) && isset($address['longitude']) )
            let marker = new google.maps.Marker({
                position: new google.maps.LatLng({{ $address['latitude'] }},
                    {{ $address['longitude'] }}),
                map: map,
                title: "{{ $order?->customer ? $order?->customer->f_name .' '. $order?->customer->l_name : $address['contact_person_name'] }}",
                icon: "{{ dynamicAsset('public/assets/admin/img/customer_location.png') }}"
            });

            google.maps.event.addListener(marker, 'click', (function(marker) {
                return function() {
                    infowindow.setContent(
                        `<div class='float--left'><img class='js--design-1' onerror="this.src='{{ dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}'"  src='{{ $order?->customer ? dynamicStorage('storage/app/public/profile/' . $order?->customer?->image) : dynamicAsset('public/assets/admin/img/160x160/img3.png') }}'></div><div class='float--right p--10px'><b>{{ $order?->customer ? $order?->customer->f_name .' '. $order?->customer->l_name : $address['contact_person_name'] }}</b><br/>{{ $address['address'] }}</div>`
                    );
                    infowindow.open(map, marker);
                }
            })(marker));
            locationbounds.extend(marker.getPosition());
            @endif
            //-----end block------
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            let markers = [];
            const bounds = new google.maps.LatLngBounds();
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();

                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    console.log(place.geometry.location);
                    if(!google.maps.geometry.poly.containsLocation(
                        place.geometry.location,
                        zonePolygon
                    )){
                        toastr.error('{{ translate('messages.out_of_coverage') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        return false;
                    }

                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();

                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };
                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );

                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
            @if ($order->restaurant)
                $.get({
                    url: '{{ url('/') }}/admin/zone/get-coordinates/{{ $order->restaurant->zone_id }}',
                    dataType: 'json',
                    success: function(data) {
                        zonePolygon = new google.maps.Polygon({
                            paths: data.coordinates,
                            strokeColor: "#FF0000",
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: 'white',
                            fillOpacity: 0,
                        });
                        zonePolygon.setMap(map);
                        zonePolygon.getPaths().forEach(function(path) {
                            path.forEach(function(latlng) {
                                bounds.extend(latlng);
                                map.fitBounds(bounds);
                            });
                        });
                        map.setCenter(data.center);
                        google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                            infoWindow.close();
                            // Create a new InfoWindow.
                            infoWindow = new google.maps.InfoWindow({
                                position: mapsMouseEvent.latLng,
                                content: JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                                    2),
                            });
                            let coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                            coordinates = JSON.parse(coordinates);

                            document.getElementById('latitude').value = coordinates['lat'];
                            document.getElementById('longitude').value = coordinates['lng'];
                            infoWindow.open(map);
                            const geocoder = new google.maps.Geocoder();
                            geocoder.geocode({ location: mapsMouseEvent.latLng }, function (results, status) {
                                if (status === "OK") {
                                    if (results[0]) {
                                        document.getElementById('address').value = results[0].formatted_address;
                                    }
                                }
                            });
                        });
                    },
                });
            @endif

        }
        initMap();

        $(document).ready(function() {

            // Re-init map before show modal
            $('#myModal').on('shown.bs.modal', function(event) {
                let button = $(event.relatedTarget);
                $("#dmassign-map").css("width", "100%");
                $("#map_canvas").css("width", "100%");
            });

            // Trigger map resize event after modal shown
            $('#myModal').on('shown.bs.modal', function() {
                initializeGMap();
                google.maps.event.trigger(map, "resize");
                map.setCenter(myLatlng);
            });

            $('#shipping-address-modal').on('shown.bs.modal', function() {
                initMap();
            });


            function initializegLocationMap() {
                map = new google.maps.Map(document.getElementById("location_map_canvas"), myOptions);

                let infowindow = new google.maps.InfoWindow();
                @if (isset($address) && isset($address['latitude']) && isset($address['longitude']) )
                    let marker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ $address['latitude'] }},
                            {{ $address['longitude'] }}),
                        map: map,
                        title: "{{ $order?->customer ? $order?->customer->f_name .' '. $order?->customer->l_name : $address['contact_person_name'] }}",
                        icon: "{{ dynamicAsset('public/assets/admin/img/customer_location.png') }}"
                    });

                    google.maps.event.addListener(marker, 'click', (function(marker) {
                        return function() {
                            infowindow.setContent(
                                `<div class='float--left'><img class='js--design-1' onerror="this.src='{{ dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}'"  src='{{ $order?->customer ? dynamicStorage('storage/app/public/profile/' . $order?->customer?->image) : dynamicAsset('public/assets/admin/img/160x160/img3.png') }}'></div><div class='float--right p--10px'><b>{{ $order?->customer ? $order?->customer->f_name .' '. $order?->customer->l_name : $address['contact_person_name'] }}</b><br/>{{ $address['address'] }}</div>`
                            );
                            infowindow.open(map, marker);
                        }
                    })(marker));
                    locationbounds.extend(marker.getPosition());
                @endif
                @if ($order->delivery_man && $order->dm_last_location)
                    let dmmarker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ $order->dm_last_location['latitude'] }},
                            {{ $order->dm_last_location['longitude'] }}),
                        map: map,
                        title: "{{ $order->delivery_man->f_name }}  {{ $order->delivery_man->l_name }}",
                        icon: "{{ dynamicAsset('public/assets/admin/img/delivery_boy_map_2.png') }}"
                    });

                    google.maps.event.addListener(dmmarker, 'click', (function(dmmarker) {
                        return function() {
                            infowindow.setContent(
                                `<div class='float--left'><img class='js--design-1 mt-2' onerror="this.src='{{ dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}'"  src='{{ dynamicStorage('storage/app/public/delivery-man/' . $order->delivery_man->image) }}'></div><div class='float--right p--10px'><b>{{ $order->delivery_man->f_name }}  {{ $order->delivery_man->l_name }}</b><br/>  <div> {{ translate('messages.Active_Orders') }} : {{ $order->delivery_man->current_orders }} </div> </b> {{ $order->dm_last_location['location'] }}</div>`
                            );
                            infowindow.open(map, dmmarker);
                        }
                    })(dmmarker));
                    locationbounds.extend(dmmarker.getPosition());
                @endif

                @if ($order->restaurant)
                    let Retaurantmarker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ $order->restaurant->latitude }},
                            {{ $order->restaurant->longitude }}),
                        map: map,
                        title: "{{ Str::limit($order->restaurant->name, 15, '...') }}",
                        icon: "{{ dynamicAsset('public/assets/admin/img/restaurant_map_1.png') }}"
                    });

                    google.maps.event.addListener(Retaurantmarker, 'click', (function(Retaurantmarker) {
                        return function() {
                            infowindow.setContent(
                                `<div class='float--left'><img class='js--design-1' onerror="this.src='{{ dynamicAsset('public/assets/admin/img/160x160/img1.jpg') }}'"  src='{{ dynamicStorage('storage/app/public/restaurant/' . $order->restaurant->logo) }}'></div><div class='float--right p--10px'><b>{{ Str::limit($order->restaurant->name, 15, '...') }}</b><br/> {{ $order->restaurant->address }}</div>`
                            );
                            infowindow.open(map, Retaurantmarker);
                        }
                    })(Retaurantmarker));
                    locationbounds.extend(Retaurantmarker.getPosition());
                @endif

                google.maps.event.addListenerOnce(map, 'idle', function() {
                    map.fitBounds(locationbounds);
                });
            }

            // Re-init map before show modal
            $('#locationModal').on('shown.bs.modal', function(event) {
                initializegLocationMap();
            });



            $('.dm_list').on('click', function() {
                let id = $(this).data('id');
                map.panTo(dmMarkers[id].getPosition());
                map.setZoom(13);
                dmMarkers[id].setAnimation(google.maps.Animation.BOUNCE);
                window.setTimeout(() => {
                    dmMarkers[id].setAnimation(null);
                }, 3);
            });




            $('.dm_list_selected').on('click', function() {
                let id = $(this).data('id');
                map.panTo(dmMarkers[id].getPosition());
                map.setZoom(13);
                dmMarkers[id].setAnimation(google.maps.Animation.BOUNCE);
                window.setTimeout(() => {
                    dmMarkers[id].setAnimation(null);
                }, 3);

            });

        })

        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'order_proof[]',
                maxCount: 6-{{ ($order->order_proof && is_array($order->order_proof))?count(json_decode($order->order_proof)):0 }},
                rowHeight: '100px !important',
                groupClassName: 'spartan_item_wrapper min-w-100px max-w-100px',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ dynamicAsset('public/assets/admin/img/upload.png') }}",
                    width: '100px'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function(index, file) {
                    toastr.error(
                        "{{ translate('messages.please_only_input_png_or_jpg_type_file') }}", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                },
                onSizeErr: function(index, file) {
                    toastr.error("{{ translate('messages.file_size_too_big') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });


    </script>



@endpush
