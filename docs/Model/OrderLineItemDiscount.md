# OrderLineItemDiscount

## Properties
Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** | The discount&#39;s name. | [optional] 
**type** | **string** | The type of the discount. If it is created by API, it would be either FIXED_PERCENTAGE or FIXED_AMOUNT as VARIABLE_* is not supported in API because the order is created at the time of sale and either percentage or amount has to be specified. | [optional] 
**percentage** | **string** | The percentage of the tax, as a string representation of a decimal number. A value of &#x60;7.25&#x60; corresponds to a percentage of 7.25%. | [optional] 
**amount_money** | [**\SquareConnect\Model\Money**](Money.md) | The amount of the discount. | [optional] 
**applied_money** | [**\SquareConnect\Model\Money**](Money.md) | The amount of the money applied by the discount in an order. | [optional] 
**scope** | **string** | The scope of the discount. | [optional] 

[[Back to Model list]](../README.md#documentation-for-models) [[Back to API list]](../README.md#documentation-for-api-endpoints) [[Back to README]](../README.md)


