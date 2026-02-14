
double check the numbers in the calculations

approved is the same as baseline
whats the differences?
baseline_budget — the original fixed budget, never changes after initial setup
approved_budget — the current approved budget, which changes over time via budget adjustments (CreateBudgetAdjustment adds/subtracts from it)
how and who makes changes to the budgets?

baseline is when you are awarded the contract the client at the completion of tender
approved then value engineers then figure out the actual cost
we need  both of these fields on http://localhost:8002/projects/5/budget


http://localhost:8001/projects/9/executive-summary
add comments to why there are variances
get this from the exec summary report under the table

implement ACL and company 
-- double check who can edit and delete packages and line items

when you save cost to date qty should that update all future values?

need to go to "add line items" page when setting up a project if only budgets were imported

need a import historical values as part of setting up a new project if there are periods that need filling in

once the project is setup add a "lock" to the project so only engineers can add CTD qty values
-- only super admin can unlocked

wheres auditing? who changed what?

when you add the cost to date quantity show the previous month above the amount your about to add so that you know what the culmation should be, this actually should be so the guy can add the cost to date this month and then it becomes a running total

Forecast is a max of either the original forecast or cost to date (flagged why is it higher)

taufs sheets are self perform, so what we are calling cost detail is actually another report

so we need to implement "data entry" mode and then you look at the cost detail as a report

rates may change month to month, we need to capture actual every single months rate vs qty and only apply the new rate going forward
after the rate changes the cost to complete changes as well
and the forecast because its now in the future going forward

==================================

scenarios to go through

 - new project
 - existing project (add/edit content)
 - import new that was existing


need to make amount automatic when adding a new lineitem