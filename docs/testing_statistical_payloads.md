# Analytics Engine Payload Testing Guide

This guide provides exactly structured JSON payloads that you can send directly to the APIs Hub `api/compute-kpi` endpoint to test each of our new advanced statistical models.

Each example maps to a real-world marketing scenario from the `custom_kpis_glossary.md` document, utilizing `facebook_marketing`, `facebook_organic`, and `google_search_console`.

---

## 1. Multiple Linear Regression & Correlation

**Goal:** Discover true marginal costs across blended channels. Example: How much does a combined Facebook and Google click truly cost on the margin over time?

```json
{
    "ast": {
        "type": "operator",
        "operator": "/",
        "left": {
            "type": "metric",
            "metric": "facebook_marketing.spend",
            "filters": {
                "channeledCampaign": 280
            }
        },
        "right": {
            "type": "operator",
            "operator": "+",
            "left": {
                "type": "metric",
                "metric": "facebook_marketing.clicks",
                "filters": {
                    "channeledCampaign": 280
                }
            },
            "right": {
                "type": "metric",
                "metric": "google_search_console.clicks",
                "filters": {
                    "site": "https://example.com"
                }
            }
        }
    },
    "filters": {
        "startDate": "2026-03-15",
        "endDate": "2026-06-02",
        "groupBy": ["daily"]
    },
    "calculate_regression": true,
    "admin_api_key": "[API-KEY]"
}
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "baseline_intercept": 5.162711376885996,
        "coefficients": {
            "x1": 0.10005539337802112
        },
        "r_squared": 0.5677391172735311,
        "data_points": 62
    }
}
```

**Interpretation:**

- `baseline_intercept`: The base value when the independent variable is zero.
- `coefficients.x1`: Every additional unit of the independent variable (combined clicks) costs an estimated $0.10 on the margin.
- `r_squared`: A value of `0.56` indicates a moderate model fit, meaning 56% of the variance is explained by the model.

**Marketing Interpretation:**

- Even if platform reporting claims a different CPC, our true blended marginal cost to acquire one additional click across both channels is currently $0.10.

---

## 2. Elasticity (Log-Log Regression)

**Goal:** Find the diminishing returns ceiling. Example: How much does a 10% increase in Facebook Spend _actually_ yield in Facebook Clicks?

```json
{
    "ast": {
        "type": "operator",
        "operator": "/",
        "left": {
            "type": "metric",
            "metric": "facebook_marketing.clicks",
            "filters": {
                "channeledCampaign": 280
            }
        },
        "right": {
            "type": "metric",
            "metric": "facebook_marketing.spend",
            "filters": {
                "channeledCampaign": 280
            }
        }
    },
    "filters": {
        "startDate": "2026-03-15",
        "endDate": "2026-06-02",
        "groupBy": ["daily"]
    },
    "calculate_elasticity": true,
    "admin_api_key": "[API-KEY]"
}
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "elasticity_coefficients": {
            "x1": 1.5972285450156722
        },
        "baseline_log_intercept": 0.05926843352417299,
        "r_squared": 0.693673763010507,
        "data_points": 62
    }
}
```

**Interpretation:**

- `elasticity_coefficients.x1`: A 1% increase in Facebook spend yields an approximate 1.59% increase in Facebook clicks. This indicates high elasticity (returns are still scaling positively).
- `r_squared`: `0.69` indicates a moderate to strong fit for the log-log regression model.

**Marketing Interpretation:**

- Because the elasticity is greater than 1 (1.59), our Facebook spend is highly scalable right now. We haven't hit diminishing returns yet; increasing budget will actually accelerate click volume at an increasing rate.

---

## 3. Autocorrelation & Seasonality

**Goal:** Prove weekly seasonality. Example: Does our Facebook Organic reach on a given day predictably correlate with our reach exactly 7 days prior?

> **Note:** This is a univariate model, so we DO NOT use an operator bridge. We just pass the raw metric node.

```json
{
    "ast": {
        "type": "metric",
        "metric": "facebook_organic.reach",
        "filters": {
            "channeledAccount": 5
        }
    },
    "filters": {
        "startDate": "2026-03-15",
        "endDate": "2026-06-02",
        "groupBy": ["daily"]
    },
    "calculate_autocorrelation": true,
    "admin_api_key": "[API-KEY]"
}
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "autocorrelation": 0.43191976097105683,
        "lag_days": 7,
        "data_points": 69
    }
}
```

**Interpretation:**

- `autocorrelation`: A value of `0.43` indicates a moderate positive correlation.
- `lag_days`: `7` confirms there is a 7-day seasonality pattern. The metric value is moderately predictive of its value exactly 7 days later.

**Marketing Interpretation:**

- Our organic reach is highly dependent on the day of the week. If we had a strong Sunday this week, we can reliably predict another strong Sunday next week. We should align our most important organic posts with these recurring weekly peaks.

---

## 4. Granger Causality

**Goal:** Predictive Attribution / Halo Effect. Example: Does spending money on Facebook Ads _cause_ a delayed spike in Facebook Organic Reach a few days later?
_(Y = FB Organic Reach, X = FB Spend)_

```json
{
    "ast": {
        "type": "operator",
        "operator": "/",
        "left": {
            "type": "metric",
            "metric": "facebook_organic.reach",
            "filters": {
                "channeledAccount": 5
            }
        },
        "right": {
            "type": "metric",
            "metric": "facebook_marketing.spend",
            "filters": {
                "channeledCampaign": 280
            }
        }
    },
    "filters": {
        "startDate": "2026-03-15",
        "endDate": "2026-06-02",
        "groupBy": ["daily"]
    },
    "calculate_granger": true,
    "admin_api_key": "[API-KEY]"
}
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "predictive": false,
        "p_value": 0.20786346755657867,
        "lag_days": 3,
        "data_points": 53
    }
}
```

**Interpretation:**

- `predictive`: `false` indicates no reliable directional causal relationship was detected.
- `p_value`: `0.207` is not statistically significant (typically > `0.05`), meaning the null hypothesis is true and the prediction is unreliable.
- `lag_days`: `3` is the evaluated delay, but because `predictive` is false, this relationship is ignored.

**Marketing Interpretation:**

- We did NOT find a statistically significant 'Halo Effect'. Our paid Facebook Ads are not reliably driving a delayed spike in Facebook Organic Reach for this specific data range. We should not assume that scaling Facebook spend will artificially inflate these organic reach metrics.

---

## 5. MACD (Momentum Shifts)

**Goal:** Detect when performance momentum flips. Example: Is our Facebook CPC getting cheaper or more expensive on a rolling basis?

> **Note:** MACD is univariate. We pass the math formula for CPC `(Spend / Clicks)` as a single combined AST block, and the PHP engine will evaluate the math first before sending the resulting array to Python.

```json
{
    "ast": {
        "type": "operator",
        "operator": "/",
        "left": {
            "type": "metric",
            "metric": "facebook_marketing.spend",
            "filters": {
                "channeledCampaign": 280
            }
        },
        "right": {
            "type": "metric",
            "metric": "facebook_marketing.clicks",
            "filters": {
                "channeledCampaign": 280
            }
        }
    },
    "filters": {
        "startDate": "2026-03-15",
        "endDate": "2026-06-02",
        "groupBy": ["daily"]
    },
    "calculate_macd": true,
    "admin_api_key": "[API-KEY]"
}
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "macd": -0.026590864993902064,
        "signal": -0.030613078615301832,
        "histogram": 0.004022213621399769,
        "momentum": "accelerating",
        "last_date": "2026-06-02"
    }
}
```

**Interpretation:**

- `macd` > `signal`: The short-term moving average has crossed above the long-term moving average.
- `momentum`: `"accelerating"` confirms that the metric (CPC) is currently in an upward trend (getting more expensive) based on recent data compared to historical data.

**Marketing Interpretation:**

- Our Facebook ads are losing efficiency. While long-term CPC might look fine, the short-term momentum shows costs are accelerating upward. We need to refresh our ad creatives or adjust audience targeting before this trend drains the budget.

---

## 6. Anomaly Detection (Rolling Z-Score)

**Goal:** Automated Alerting. Example: Did our Facebook Marketing impressions spike unexpectedly outside of the normal 7-day standard deviation?

> **Note:** This is a univariate model. We pass the single Facebook Marketing metric.

```json
{
    "ast": {
        "type": "metric",
        "metric": "facebook_marketing.impressions",
        "filters": {
            "channeledCampaign": 280
        }
    },
    "filters": {
        "startDate": "2026-03-15",
        "endDate": "2026-06-02",
        "groupBy": ["daily"]
    },
    "calculate_anomaly": true,
    "admin_api_key": "[API-KEY]"
}
```

**Expected Response:**

```json
{
    "success": true,
    "data": {
        "anomaly_detected": true,
        "anomaly_dates": ["2026-05-12"],
        "threshold_z": 2,
        "data_points": 62
    }
}
```

**Interpretation:**

- `anomaly_detected`: `true` confirms that statistical outliers exist in the dataset.
- `anomaly_dates`: Lists the specific date(s) (e.g., 2026-05-12) where the metric value deviated significantly from its rolling average.
- `threshold_z`: `2` indicates that the flagged date(s) had values falling outside of 2 standard deviations from the norm.

**Marketing Interpretation:**

- Facebook Marketing recorded a highly unusual spike in ad impressions on 2026-05-12 that cannot be explained by normal day-to-day variance. We should investigate if a specific campaign gained sudden algorithmic traction, if audience competition dropped, or if there was a budget pacing anomaly on this day.
