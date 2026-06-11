# Custom KPIs & Marketing Statistics Glossary

This glossary outlines the standard marketing formulas, Custom KPIs, and advanced statistical models available (or planned) within the APIs Hub Facade.

## 1. Foundational Custom KPIs
These are standard, mathematically deterministic metrics calculated natively by evaluating the AST directly against the database aggregations.

### Efficiency Metrics
- **Blended ROAS (Return on Ad Spend)**
  - *Formula*: `(shopify.sales + bigcommerce.sales) / (facebook_marketing.spend + google_search_console.spend)`
  - *Use*: Measures total omni-channel revenue efficiency.
- **Blended CPA (Cost Per Acquisition)**
  - *Formula*: `(facebook_marketing.spend + google_search_console.spend) / (shopify.orders + bigcommerce.orders)`
  - *Use*: Tracks the true cost of acquiring a customer across all active channels.
- **MER (Marketing Efficiency Ratio)**
  - *Formula*: `Total Store Revenue / Total Marketing Spend`
  - *Use*: The ultimate macro-level profitability indicator.

### Engagement Metrics
- **Blended CTR (Click-Through Rate)**
  - *Formula*: `(facebook_marketing.clicks + google_search_console.clicks) / (facebook_marketing.impressions + google_search_console.impressions)`
- **Blended CPC (Cost Per Click)**
  - *Formula*: `Total Spend / Total Clicks`

---

## 2. Advanced Statistical Endpoints (Python Engine)
These models utilize the external Python Analytics Engine to derive probabilistic insights, predictive modeling, and anomaly detection from the foundational KPI time-series data.

### Linear Regression & Correlation
- **Endpoint**: `/api/v1/stats/regression`
- **Output**: `r_squared` (Correlation strength), `coefficients` (Marginal cost/value), `baseline_intercept` (Fixed base value).
- **Marketing Use Cases**:
  - *Marginal CPA*: Discovering the true incremental cost of the next conversion.
  - *Platform Halo Effect*: Measuring how heavily Facebook Spend correlates with Organic Google Search traffic.

### Autocorrelation & Seasonality Detection
- **Description**: Measures a variable's correlation against its own historical lagged values (e.g., comparing today vs. 7 days ago).
- **Marketing Use Cases**:
  - *Proving Weekly Seasonality*: Mathematically verifying if weekends are consistently underperforming, enabling automated day-of-week bid modifiers.
  - *Ad Fatigue Detection*: Identifying the exact timeframe when a creative's performance begins to negatively correlate with its historical baseline.

### Log-Log Regression (Elasticity Modeling)
- **Description**: Calculates the percentage change in a dependent variable resulting from a 1% change in an independent variable.
- **Marketing Use Cases**:
  - *Diminishing Returns*: Identifying the budget ceiling. E.g., proving that a 10% increase in spend is currently only yielding a 4% increase in revenue (Elasticity = 0.4).

### Granger Causality
- **Description**: A statistical test to determine whether one time series is useful in forecasting another.
- **Marketing Use Cases**:
  - *Predictive Attribution*: Proving that top-of-funnel Brand Spend directly *causes* a delayed spike in bottom-of-funnel Search Conversions 3 days later.

### Moving Average Convergence Divergence (MACD)
- **Description**: Compares short-term exponential moving averages against long-term averages to detect trend reversals.
- **Marketing Use Cases**:
  - *Momentum Detection*: Automatically flagging the exact day when Campaign CPA momentum flips from "getting cheaper" to "getting more expensive," signaling the exact optimal time to scale down budgets.

### Isolation Forests / Z-Score (Anomaly Detection)
- **Description**: Automatically identifies dates where the values deviate significantly from expected rolling standard deviations.
- **Marketing Use Cases**:
  - *Automated Alerting*: Triggering webhook alerts when yesterday's CPC spiked 3x higher than the 30-day norm.
