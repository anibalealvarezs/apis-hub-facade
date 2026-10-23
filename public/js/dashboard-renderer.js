/* ─── Dashboard Widget Renderer ───
 * Renders all 7 widget types: tile, line_chart, bar_chart, table, gauge, sparkline, anomaly_list
 * Each widget container calls `fetchWidgetData(widgetId, containerEl)` on mount.
 */

window.dashboardRenderer = {
    _chartInstances: new Map(),
    _widgetData: new Map(),
    _pinnedTooltips: new Map(),
    _retryTimers: new Map(),
    METRIC_FORMATS: {
        spend: { label: "Spend", format: "currency", prefix: "$" },
        cpc: {
            label: "CPC",
            format: "currency",
            prefix: "$",
            lower_is_better: true,
        },
        cpm: {
            label: "CPM",
            format: "currency",
            prefix: "$",
            lower_is_better: true,
        },
        revenue: { label: "Revenue", format: "currency", prefix: "$" },
        purchase_roas: { label: "ROAS", format: "currency", prefix: "$" },
        aov: { label: "AOV", format: "currency", prefix: "$" },
        cost_per_result: {
            label: "Cost/Result",
            format: "currency",
            prefix: "$",
            lower_is_better: true,
        },
        frequency: {
            label: "Frequency",
            format: "number",
            lower_is_better: true,
        },
        result_rate: {
            label: "Result Rate",
            format: "percentage",
            multiply: 100,
        },
        ctr: { label: "CTR", format: "percentage", multiply: 100 },
        bounce_rate: {
            label: "Bounce Rate",
            format: "percentage",
            multiply: 100,
            lower_is_better: true,
        },
        clicks: { label: "Clicks", format: "number" },
        impressions: { label: "Impressions", format: "number" },
        reach: { label: "Reach", format: "number" },
        conversions: { label: "Conversions", format: "number" },
        results: { label: "Results", format: "number" },
        sessions: { label: "Sessions", format: "number" },
        pageviews: { label: "Pageviews", format: "number" },
        new_users: { label: "New Users", format: "number" },
        followers: { label: "Followers", format: "number" },
        engaged_users: { label: "Engaged Users", format: "number" },
        orders: { label: "Orders", format: "number" },
        sends: { label: "Sends", format: "number" },
        opens: { label: "Opens", format: "number" },
        bounces: { label: "Bounces", format: "number" },
        link_clicks: { label: "Link Clicks", format: "number" },
        engagements: { label: "Engagements", format: "number" },
        total_interactions: { label: "Total Interactions", format: "number" },
        average_session_duration: {
            label: "Avg Session",
            format: "number",
            suffix: "s",
        },
        position: {
            label: "Avg Position",
            format: "number",
            lower_is_better: true,
        },
    },

    // Ratio KPIs produce a result whose type differs from the raw dependent metric.
    // Determine the display format for the KPI output based on metric[0] (dependent) and metric[1] (independent).
    getKpiResultFormat(controls) {
        const m0 = controls?.metrics?.[0];
        const m1 = controls?.metrics?.[1];
        if (!m0) return null;
        const f0 = this.METRIC_FORMATS[m0];
        const f1 = m1 ? this.METRIC_FORMATS[m1] : null;

        // Regression and scatter plot models preserve the raw dependent metric formatting
        const calcType =
            controls?.calculation_type || controls?.calculationType || "";
        const widgetType = controls?.widget_type || controls?.widgetType || "";
        const isRegressionOrScatter =
            calcType === "calculate_regression" ||
            widgetType === "scatter_plot";
        if (isRegressionOrScatter) {
            return f0 || null;
        }

        // If multiple metrics exist AND this is a KPI ratio widget, apply ratio heuristics.
        if (m1 && controls?.source_type === "kpi") {
            // Known ratio → output type
            const ratioFormats = {
                "clicks/impressions": {
                    label: "CTR",
                    format: "percentage",
                    multiply: 100,
                },
                "conversions/impressions": {
                    label: "Conv. Rate",
                    format: "percentage",
                    multiply: 100,
                },
                "conversions/clicks": {
                    label: "Conv. Rate",
                    format: "percentage",
                    multiply: 100,
                },
                "results/impressions": {
                    label: "Result Rate",
                    format: "percentage",
                    multiply: 100,
                },
                "results/clicks": {
                    label: "Result Rate",
                    format: "percentage",
                    multiply: 100,
                },
                "sessions/clicks": {
                    label: "Session Rate",
                    format: "percentage",
                    multiply: 100,
                },
                "sessions/link_clicks": {
                    label: "Session Rate",
                    format: "percentage",
                    multiply: 100,
                },
                "bounce_rate/clicks": {
                    label: "Bounce Rate",
                    format: "percentage",
                    multiply: 100,
                    lower_is_better: true,
                },
                "spend/clicks": {
                    label: "CPC",
                    format: "currency",
                    prefix: "$",
                    lower_is_better: true,
                },
                "spend/impressions": {
                    label: "CPM",
                    format: "currency",
                    prefix: "$",
                    lower_is_better: true,
                },
                "spend/conversions": {
                    label: "CPA",
                    format: "currency",
                    prefix: "$",
                    lower_is_better: true,
                },
                "spend/results": {
                    label: "Cost/Result",
                    format: "currency",
                    prefix: "$",
                    lower_is_better: true,
                },
                "spend/sessions": {
                    label: "Cost/Session",
                    format: "currency",
                    prefix: "$",
                    lower_is_better: true,
                },
                "revenue/spend": {
                    label: "ROAS",
                    format: "currency",
                    prefix: "$",
                },
                "revenue/clicks": {
                    label: "RPC",
                    format: "currency",
                    prefix: "$",
                },
                "impressions/spend": { label: "Impr./$", format: "number" },
                "sessions/spend": { label: "Sessions/$", format: "number" },
                "new_users/spend": { label: "New Users/$", format: "number" },
                "engaged_users/reach": {
                    label: "Eng. Rate",
                    format: "percentage",
                    multiply: 100,
                },
                "average_session_duration/clicks": {
                    label: "Session/Click",
                    format: "number",
                    suffix: "s",
                },
                "clicks/position": { label: "Clicks/Pos.", format: "number" },
            };
            const key = m0 + "/" + m1;
            if (ratioFormats[key]) return ratioFormats[key];

            // Fallback heuristic
            if (f0?.format === "currency" && f1?.format === "number") {
                return { label: f0.label, format: "currency", prefix: "$" };
            }
            if (m0 === "position" || m1 === "position") {
                return f0 || null;
            }
            if (f0?.format === "number" && f1?.format === "number") {
                return {
                    label: f0.label + "/" + f1.label,
                    format: "percentage",
                    multiply: 100,
                };
            }
            return f0 || null;
        }

        return f0 || null;
    },

    BRAND_ICONS: {
        facebook: { viewBox: "0 0 24 24", path: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' },
        instagram: { viewBox: "0 0 24 24", path: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z' },
        google: { viewBox: "0 0 24 24", path: 'M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z' },
        google_analytics: { viewBox: "0 0 24 24", path: 'M21.36 10.96a2.64 2.64 0 0 0-2.64 2.64v7.76a2.64 2.64 0 1 0 5.28 0V13.6a2.64 2.64 0 0 0-2.64-2.64zM12 5.68A2.64 2.64 0 0 0 9.36 8.32v13.04a2.64 2.64 0 1 0 5.28 0V8.32A2.64 2.64 0 0 0 12 5.68zM2.64 16.24A2.64 2.64 0 0 0 0 18.88v2.48a2.64 2.64 0 0 0 5.28 0v-2.48a2.64 2.64 0 0 0-2.64-2.64z' },
        google_search_console: { viewBox: "0 0 24 24", path: 'M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14zm-2-3h1.5v-3H7.5v3zm3 0H12V7h-1.5v4z' },
        google_ads: { viewBox: "0 0 24 24", path: 'M3.9998 22.9291C1.7908 22.9291 0 21.1383 0 18.9293s1.7908-3.9998 3.9998-3.9998 3.9998 1.7908 3.9998 3.9998-1.7908 3.9998-3.9998 3.9998zm19.4643-6.0004L15.4632 3.072C14.3586 1.1587 11.9121.5028 9.9988 1.6074S7.4295 5.1585 8.5341 7.0718l8.0009 13.8567c1.1046 1.9133 3.5511 2.5679 5.4644 1.4646 1.9134-1.1046 2.568-3.5511 1.4647-5.4644zM7.5137 4.8438L1.5645 15.1484A4.5 4.5 0 0 1 4 14.4297c2.5597-.0075 4.6248 2.1585 4.4941 4.7148l3.2168-5.5723-3.6094-6.25c-.4499-.7793-.6322-1.6394-.5878-2.4784z' },
        mailchimp: { viewBox: "0 0 24 24", path: 'M11.267 0C6.791-.015-1.82 10.246 1.397 12.964l.79.669a3.88 3.88 0 0 0-.22 1.792c.084.84.518 1.644 1.22 2.266.666.59 1.542.964 2.392.964 1.406 3.24 4.62 5.228 8.386 5.34 4.04.12 7.433-1.776 8.854-5.182.093-.24.488-1.316.488-2.267 0-.956-.54-1.352-.885-1.352-.01-.037-.078-.286-.172-.586-.093-.3-.19-.51-.19-.51.375-.563.382-1.065.332-1.35-.053-.353-.2-.653-.496-.964-.296-.311-.902-.63-1.753-.868l-.446-.124c-.002-.019-.024-1.053-.043-1.497-.014-.32-.042-.822-.197-1.315-.186-.668-.508-1.253-.911-1.627 1.112-1.152 1.806-2.422 1.804-3.511-.003-2.095-2.576-2.729-5.746-1.416l-.672.285A678.22 678.22 0 0 0 12.7.504C12.304.159 11.817.002 11.267 0zm.073.873c.166 0 .322.019.465.058.297.084 1.28 1.224 1.28 1.224s-1.826 1.013-3.52 2.426c-2.28 1.757-4.005 4.311-5.037 7.082-.811.158-1.526.618-1.963 1.253-.261-.218-.748-.64-.834-.804-.698-1.326.761-3.902 1.781-5.357C5.834 3.44 9.37.867 11.34.873zm3.286 3.273c.04-.002.06.05.028.074-.143.11-.299.26-.413.414a.04.04 0 0 0 .031.064c.659.004 1.587.235 2.192.574.041.023.012.103-.034.092-.915-.21-2.414-.369-3.97.01-1.39.34-2.45.863-3.224 1.426-.04.028-.086-.023-.055-.06.896-1.035 1.999-1.935 2.987-2.44.034-.018.07.019.052.052-.079.143-.23.447-.278.678-.007.035.032.063.062.042.615-.42 1.684-.868 2.622-.926zm3.023 3.205l.056.001a.896.896 0 0 1 .456.146c.534.355.61 1.216.638 1.845.015.36.059 1.229.074 1.478.034.571.184.651.487.751.17.057.33.098.563.164.706.198 1.125.4 1.39.658.157.162.23.333.253.497.083.608-.472 1.36-1.942 2.041-1.607.746-3.557.935-4.904.785l-.471-.053c-1.078-.145-1.693 1.247-1.046 2.201.417.615 1.552 1.015 2.688 1.015 2.604 0 4.605-1.111 5.35-2.072a.987.987 0 0 0 .06-.085c.036-.055.006-.085-.04-.054-.608.416-3.31 2.069-6.2 1.571 0 0-.351-.057-.672-.182-.255-.1-.788-.344-.853-.891 2.333.72 3.801.039 3.801.039a.072.072 0 0 0 .042-.072.067.067 0 0 0-.074-.06s-1.911.283-3.718-.378c.197-.64.72-.408 1.51-.345a11.045 11.045 0 0 0 3.647-.394c.818-.234 1.892-.697 2.727-1.356.281.618.38 1.299.38 1.299s.219-.04.4.073c.173.106.299.326.213.895-.176 1.063-.628 1.926-1.387 2.72a5.714 5.714 0 0 1-1.666 1.244c-.34.18-.704.334-1.087.46-2.863.935-5.794-.093-6.739-2.3a3.545 3.545 0 0 1-.189-.522c-.403-1.455-.06-3.2 1.008-4.299.065-.07.132-.153.132-.256 0-.087-.055-.179-.102-.243-.374-.543-1.669-1.466-1.409-3.254.187-1.284 1.31-2.189 2.357-2.135.089.004.177.01.266.015.453.027.85.085 1.223.1.625.028 1.187-.063 1.853-.618.225-.187.405-.35.71-.401.028-.005.092-.028.215-.028zm.022 2.18a.42.42 0 0 0-.06.005c-.335.054-.347.468-.228 1.04.068.32.187.595.32.765.175-.02.343-.022.498 0 .089-.205.104-.557.024-.942-.112-.535-.261-.872-.554-.868zm-3.66 1.546a1.724 1.724 0 0 0-1.016.326c-.16.117-.311.28-.29.378.008.032.031.056.088.063.131.015.592-.217 1.122-.25.374-.023.684.094.923.2.239.104.386.173.443.113.037-.038.026-.11-.031-.204-.118-.192-.36-.387-.618-.497a1.601 1.601 0 0 0-.621-.129zm4.082.81c-.171-.003-.313.186-.317.42-.004.236.131.43.303.432.172.003.314-.185.318-.42.004-.236-.132-.429-.304-.432zm-3.58.172c-.05 0-.102.002-.155.008-.311.05-.483.152-.593.247-.094.082-.152.173-.152.237a.075.075 0 0 0 .075.076c.07 0 .228-.063.228-.063a1.98 1.98 0 0 1 1.001-.104c.157.018.23.027.265-.026.01-.016.022-.049-.01-.1-.063-.103-.311-.269-.66-.275zm2.26.4c-.127 0-.235.051-.283.148-.075.154.035.363.246.466.21.104.443.063.52-.09.075-.155-.035-.364-.246-.467a.542.542 0 0 0-.237-.058zm-11.635.024c.048 0 .098 0 .149.003.73.04 1.806.6 2.052 2.19.217 1.41-.128 2.843-1.449 3.069-.123.02-.248.029-.374.026-1.22-.033-2.539-1.132-2.67-2.435-.145-1.44.591-2.548 1.894-2.811.117-.024.252-.04.398-.042zm-.07.927a1.144 1.144 0 0 0-.847.364c-.38.418-.439.988-.366 1.19.027.073.07.094.1.098.064.008.16-.039.22-.2a1.2 1.2 0 0 0 .017-.052 1.58 1.58 0 0 1 .157-.37.689.689 0 0 1 .955-.199c.266.174.369.5.255.81-.058.161-.154.469-.133.721.043.511.357.717.64.738.274.01.466-.143.515-.256.029-.067.005-.107-.011-.125-.043-.053-.113-.037-.18-.021a.638.638 0 0 1-.16.022.347.347 0 0 1-.294-.148c-.078-.12-.073-.3.013-.504.011-.028.025-.058.04-.092.138-.308.368-.825.11-1.317-.195-.37-.513-.602-.894-.65a1.135 1.135 0 0 0-.138-.01z' },
        shopify: { viewBox: "0 0 24 24", path: 'M15.337 23.979l7.216-1.561s-2.604-17.613-2.625-17.73c-.018-.116-.114-.192-.211-.192s-1.929-.136-1.929-.136-1.275-1.274-1.439-1.411c-.045-.037-.075-.057-.121-.074l-.914 21.104h.023zM11.71 11.305s-.81-.424-1.774-.424c-1.447 0-1.504.906-1.504 1.141 0 1.232 3.24 1.715 3.24 4.629 0 2.295-1.44 3.76-3.406 3.76-2.354 0-3.54-1.465-3.54-1.465l.646-2.086s1.245 1.066 2.28 1.066c.675 0 .975-.545.975-.932 0-1.619-2.654-1.694-2.654-4.359-.034-2.237 1.571-4.416 4.827-4.416 1.257 0 1.875.361 1.875.361l-.945 2.715-.02.01zM11.17.83c.136 0 .271.038.405.135-.984.465-2.064 1.639-2.508 3.992-.656.213-1.293.405-1.889.578C7.697 3.75 8.951.84 11.17.84V.83zm1.235 2.949v.135c-.754.232-1.583.484-2.394.736.466-1.777 1.333-2.645 2.085-2.971.193.501.309 1.176.309 2.1zm.539-2.234c.694.074 1.141.867 1.429 1.755-.349.114-.735.231-1.158.366v-.252c0-.752-.096-1.371-.271-1.871v.002zm2.992 1.289c-.02 0-.06.021-.078.021s-.289.075-.714.21c-.423-1.233-1.176-2.37-2.508-2.37h-.115C12.135.209 11.669 0 11.265 0 8.159 0 6.675 3.877 6.21 5.846c-1.194.365-2.063.636-2.16.674-.675.213-.694.232-.772.87-.075.462-1.83 14.063-1.83 14.063L15.009 24l.927-21.166z' },
        klaviyo: { viewBox: "0 0 24 24", path: 'M7.053.921h9.894a6.132 6.132 0 0 1 6.132 6.132v9.894a6.132 6.132 0 0 1-6.132 6.132H7.053a6.132 6.132 0 0 1-6.132-6.132V7.053a6.132 6.132 0 0 1 6.132-6.132zM6.188 8.112v7.776h11.624l-2.44-3.888 2.44-3.888z' },
        tiktok: { viewBox: "0 0 24 24", path: 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z' },
        meta: { viewBox: "0 0 24 24", path: 'M6.915 4.03c-1.968 0-3.683 1.28-4.871 3.113C.704 9.208 0 11.883 0 14.449c0 .706.07 1.369.21 1.973a6.624 6.624 0 0 0 .265.86 5.297 5.297 0 0 0 .371.761c.696 1.159 1.818 1.927 3.593 1.927 1.497 0 2.633-.671 3.965-2.444.76-1.012 1.144-1.626 2.663-4.32l.756-1.339.186-.325c.061.1.121.196.183.3l2.152 3.595c.724 1.21 1.665 2.556 2.47 3.314 1.046.987 1.992 1.22 3.06 1.22 1.075 0 1.876-.355 2.455-.843a3.743 3.743 0 0 0 .81-.973c.542-.939.861-2.127.861-3.745 0-2.72-.681-5.357-2.084-7.45-1.282-1.912-2.957-2.93-4.716-2.93-1.047 0-2.088.467-3.053 1.308-.652.57-1.257 1.29-1.82 2.05-.69-.875-1.335-1.547-1.958-2.056-1.182-.966-2.315-1.303-3.454-1.303zm10.16 2.053c1.147 0 2.188.758 2.992 1.999 1.132 1.748 1.647 4.195 1.647 6.4 0 1.548-.368 2.9-1.839 2.9-.58 0-1.027-.23-1.664-1.004-.496-.601-1.343-1.878-2.832-4.358l-.617-1.028a44.908 44.908 0 0 0-1.255-1.98c.07-.109.141-.224.211-.327 1.12-1.667 2.118-2.602 3.358-2.602zm-10.201.553c1.265 0 2.058.791 2.675 1.446.307.327.737.871 1.234 1.579l-1.02 1.566c-.757 1.163-1.882 3.017-2.837 4.338-1.191 1.649-1.81 1.817-2.486 1.817-.524 0-1.038-.237-1.383-.794-.263-.426-.464-1.13-.464-2.046 0-2.221.63-4.535 1.66-6.088.454-.687.964-1.226 1.533-1.533a2.264 2.264 0 0 1 1.088-.285z' },
        pinterest: { viewBox: "0 0 24 24", path: 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z' },
        linkedin: { viewBox: "0 0 24 24", path: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037c-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85c3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.06 2.06 0 0 1-2.063-2.065a2.064 2.064 0 1 1 2.063 2.065m1.782 13.019H3.555V9h3.564zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z' },
        x: { viewBox: "0 0 24 24", path: 'M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z' },
    },

    /**
     * Resolves the hierarchical brand or channel SVG string.
     * Takes dataset channel, dependency, and label to handle edge cases like
     * Facebook Organic (which includes both Facebook and Instagram assets).
     */
    getChannelIconSvg(channel, dependency, label) {
        let c = String(channel || "").trim().toLowerCase();
        const dep = String(dependency || "").trim().toLowerCase();
        const lbl = String(label || "").trim().toLowerCase();

        // 1. Detect channel/provider from bracketed or prefixed label if channel isn't set directly
        // e.g. "[IG] Likes", "[Google Analytics] - Sessions", "GSC - Clics", "GA4 - Sessions", "GAds - Clicks"
        if (!c && lbl) {
            const bracketMatch = lbl.match(/^\[([a-z0-9_\-\s]+)\]/i);
            if (bracketMatch) {
                const tag = bracketMatch[1].trim().toLowerCase();
                if (['ig', 'instagram'].includes(tag)) c = 'instagram';
                else if (['fb', 'facebook'].includes(tag)) c = 'facebook';
                else if (['meta', 'meta ads', 'meta organic'].includes(tag)) c = 'meta';
                else if (['ga4', 'google analytics', 'google_analytics'].includes(tag)) c = 'google_analytics';
                else if (['gsc', 'google search console', 'google_search_console'].includes(tag)) c = 'google_search_console';
                else if (['gads', 'google ads', 'google_ads'].includes(tag)) c = 'google_ads';
                else if (['gbp', 'google business profile'].includes(tag)) c = 'google';
                else c = tag;
            } else {
                const prefixMatch = lbl.match(/^([a-z0-9_]+)\s*[-—:]/i);
                if (prefixMatch) {
                    const tag = prefixMatch[1].trim().toLowerCase();
                    if (tag === 'gsc') c = 'google_search_console';
                    else if (tag === 'ga4') c = 'google_analytics';
                    else if (tag === 'gads') c = 'google_ads';
                    else if (tag === 'ig') c = 'instagram';
                    else if (tag === 'fb') c = 'facebook';
                    else if (tag === 'meta') c = 'meta';
                }
            }
        }

        // 2. Meta / Facebook ecosystem:
        // By user requirement, Meta Ads and Meta Organic default to the official Meta infinity logo.
        // Instagram assets under Facebook Organic (e.g. instagram_account dependency or [ig] prefix) use Instagram.
        if (['meta', 'meta_ads', 'meta_organic', 'facebook_marketing', 'facebook_organic', 'facebook', 'fb'].includes(c)) {
            const isInstagram = dep === 'instagram_account' ||
                dep.includes('instagram') ||
                lbl.startsWith('[ig]') ||
                lbl.startsWith('ig - ') ||
                lbl.includes('instagram') ||
                lbl.includes('ig_post');
            if (isInstagram) {
                const icon = this.BRAND_ICONS.instagram;
                return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
            }
            if (c === 'facebook' || c === 'fb') {
                const fbIcon = this.BRAND_ICONS.facebook;
                return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${fbIcon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${fbIcon.path}"/></svg>`;
            }
            const metaIcon = this.BRAND_ICONS.meta;
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${metaIcon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${metaIcon.path}"/></svg>`;
        }

        // 3. Instagram direct match
        if (c === 'instagram' || c === 'ig') {
            const icon = this.BRAND_ICONS.instagram;
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
        }

        // 4. Google sub-channels
        if (['google_analytics', 'google_analytics_4', 'ga4'].includes(c) || lbl.startsWith('[ga4]') || lbl.startsWith('ga4 - ')) {
            const icon = this.BRAND_ICONS.google_analytics;
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
        }
        if (['google_search_console', 'gsc'].includes(c) || lbl.startsWith('[gsc]') || lbl.startsWith('gsc - ')) {
            const icon = this.BRAND_ICONS.google_search_console;
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
        }
        if (['google_ads', 'gads'].includes(c) || lbl.startsWith('[gads]') || lbl.startsWith('gads - ')) {
            const icon = this.BRAND_ICONS.google_ads;
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
        }
        if (c.startsWith('google') || lbl.startsWith('[gbp]')) {
            const icon = this.BRAND_ICONS.google;
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
        }

        // 5. Direct or provider-prefix match
        const directKey = c.replace(/-/g, '_');
        if (this.BRAND_ICONS[directKey]) {
            const icon = this.BRAND_ICONS[directKey];
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
        }
        const prefix = directKey.split('_')[0];
        if (prefix && this.BRAND_ICONS[prefix]) {
            const icon = this.BRAND_ICONS[prefix];
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${icon.viewBox}" fill="currentColor" style="width:12px;height:12px;display:inline-block;vertical-align:-1.5px;flex-shrink:0;opacity:0.85;" aria-hidden="true"><path d="${icon.path}"/></svg>`;
        }

        return "";
    },
    _concurrencyLimit: 4,
    _activeRequests: 0,
    _queue: [],

    _enqueue(fn) {
        return new Promise((resolve, reject) => {
            this._queue.push({ fn, resolve, reject });
            this._dequeue();
        });
    },

    _dequeue() {
        if (this._activeRequests >= this._concurrencyLimit || this._queue.length === 0) {
            return;
        }
        this._activeRequests++;
        const { fn, resolve, reject } = this._queue.shift();
        fn()
            .then(resolve)
            .catch(reject)
            .finally(() => {
                this._activeRequests--;
                this._dequeue();
            });
    },

    _hashString(str) {
        let hash = 5381;
        for (let i = 0; i < str.length; i++) {
            hash = ((hash << 5) + hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return Math.abs(hash).toString(36);
    },

    _getStorageCache(widgetId, effectiveTenant, body, lang) {
        try {
            const keySuffix = this._hashString(JSON.stringify(body) + "|" + (lang || ""));
            const cacheKey = `db_widget_${effectiveTenant}_${widgetId}_${keySuffix}`;
            const item = localStorage.getItem(cacheKey);
            if (!item) return null;
            const parsed = JSON.parse(item);
            // 30 minute TTL
            if (Date.now() - (parsed.timestamp || 0) > 30 * 60 * 1000) {
                localStorage.removeItem(cacheKey);
                return null;
            }
            return parsed.data;
        } catch (e) {
            return null;
        }
    },

    _setStorageCache(widgetId, effectiveTenant, body, lang, data) {
        try {
            const keySuffix = this._hashString(JSON.stringify(body) + "|" + (lang || ""));
            const cacheKey = `db_widget_${effectiveTenant}_${widgetId}_${keySuffix}`;
            localStorage.setItem(
                cacheKey,
                JSON.stringify({
                    timestamp: Date.now(),
                    data: data,
                }),
            );
        } catch (e) {
            // Storage quota exceeded or disabled
        }
    },

    clearStorageCache(widgetId) {
        try {
            const prefix = widgetId !== undefined && widgetId !== null
                ? `db_widget_`
                : "db_widget_";
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (!key) continue;
                if (widgetId !== undefined && widgetId !== null) {
                    if (key.startsWith("db_widget_") && key.includes(`_${widgetId}_`)) {
                        keysToRemove.push(key);
                    }
                } else if (key.startsWith("db_widget_")) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach((k) => localStorage.removeItem(k));
        } catch (e) {}
    },

    /**
     * Fetch data for a single widget and render into its container.
     * Data fetch happens immediately; Chart.js rendering is deferred until
     * the widget container enters the viewport (IntersectionObserver).
     * @param {number} widgetId
     * @param {HTMLElement} containerEl - The element to render into
     * @param {object} controls - Resolved controls for this widget
     * @param {string} tenant - Project subdomain/ID
     */
    async renderWidget(widgetId, containerEl, controls, tenant) {
        if (!containerEl) return;

        // Show loading
        containerEl.innerHTML = this.loadingSkeleton();

        try {
            const effectiveTenant =
                tenant ||
                window.tenant ||
                window.Filament?.getTenant?.() ||
                document.querySelector('meta[name="tenant"]')?.content ||
                window.location.pathname.match(/\/app\/([^\/]+)/)?.[1] ||
                "";
            const body = { tenant: effectiveTenant };
            if (controls) {
                const overrideKeys = [
                    "date_start",
                    "date_end",
                    "zero_handling",
                    "granularity",
                    "metrics",
                    "assets",
                    "asset_group",
                    "series_assets",
                    "series_metrics",
                    "series_channels",
                    "channel",
                    "edge_case_weighted",
                    "edge_case_grouping",
                    "max_ratio",
                    "remove_unknown",
                    "combo_chart_config",
                    "combo_series_config",
                    "series_metric_colors",
                    "series_metric_namings",
                    "raw_series",
                ];
                const overrides = {};
                for (const key of overrideKeys) {
                    if (controls[key] !== undefined)
                        overrides[key] = controls[key];
                }
                if (Object.keys(overrides).length > 0)
                    body.controls = overrides;
            }
            const currentLang =
                document.documentElement.lang ||
                window.Filament?.locale ||
                "en";

            // Check localStorage cache first
            const cachedData = this._getStorageCache(
                widgetId,
                effectiveTenant,
                body,
                currentLang,
            );
            if (cachedData) {
                if (cachedData.missing_assets || cachedData.data?._missing_assets) {
                    containerEl.innerHTML = this.emptyAssetState();
                    this._widgetData.set(containerEl, cachedData);
                    return;
                }
                this._widgetData.set(containerEl, cachedData);
                this._lazyRenderWhenVisible(containerEl, cachedData);
                return;
            }

            // Enqueue request (max 4 concurrent) with exponential backoff
            const json = await this._enqueue(async () => {
                const maxAttempts = 3;
                let lastError = null;

                for (let attempt = 1; attempt <= maxAttempts; attempt++) {
                    try {
                        const response = await fetch(
                            "/api/dashboard/widget/" +
                                widgetId +
                                "/data?lang=" +
                                encodeURIComponent(currentLang),
                            {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    Accept: "application/json",
                                    "X-CSRF-TOKEN":
                                        document.querySelector('meta[name="csrf-token"]')
                                            ?.content || "",
                                },
                                body: JSON.stringify(body),
                            },
                        );

                        if (!response.ok) {
                            const err = await response.json().catch(() => ({}));
                            const status = response.status;
                            const errMsg = err.error || "HTTP " + status;
                            // Retry on server errors or gateway timeouts
                            if (attempt < maxAttempts && (status >= 500 || status === 429)) {
                                throw new Error(errMsg);
                            }
                            throw new Error(errMsg);
                        }

                        const resJson = await response.json();

                        if (!resJson.success) {
                            const errMsg = resJson.message || resJson.error || "Unknown error";
                            if (attempt < maxAttempts && (errMsg.includes("timed out") || errMsg.includes("timeout") || errMsg.includes("cURL error 28"))) {
                                throw new Error(errMsg);
                            }
                            return resJson;
                        }

                        return resJson;
                    } catch (err) {
                        lastError = err;
                        if (attempt < maxAttempts) {
                            // Exponential backoff: 1000ms, then 2500ms
                            const delayMs = attempt === 1 ? 1000 : 2500;
                            await new Promise((r) => setTimeout(r, delayMs));
                        }
                    }
                }
                throw lastError || new Error("Failed to load widget data");
            });

            if (!json.success) {
                if (json.retryable) {
                    containerEl.innerHTML = this.cloudflareTimeoutState(
                        json.message || json.error,
                    );
                    this._scheduleRetry(
                        widgetId,
                        containerEl,
                        controls,
                        tenant,
                    );
                    return;
                }
                throw new Error(json.message || json.error || "Unknown error");
            }

            // Success — cancel any pending auto-reload for this widget
            this._cancelRetry(widgetId);

            // Save to localStorage cache
            this._setStorageCache(
                widgetId,
                effectiveTenant,
                body,
                currentLang,
                json,
            );

            if (json.missing_assets || json.data?._missing_assets) {
                containerEl.innerHTML = this.emptyAssetState();
                this._widgetData.set(containerEl, json);
                return;
            }

            this._widgetData.set(containerEl, json);

            // Render immediately if already in viewport, otherwise defer
            this._lazyRenderWhenVisible(containerEl, json);
        } catch (e) {
            if (e.message === "access_restricted") {
                containerEl.innerHTML = this.accessRestrictedState();
            } else if (
                this.isRetryableError(e)
            ) {
                containerEl.innerHTML = this.cloudflareTimeoutState(
                    e.message,
                );
                this._scheduleRetry(widgetId, containerEl, controls, tenant);
            } else if (
                e.message &&
                (e.message.includes("___EMPTY_GROUP___") ||
                    e.message.includes("no assets") ||
                    e.message.includes("No assets found") ||
                    e.message.includes("no available assets"))
            ) {
                containerEl.innerHTML = this.emptyAssetState();
            } else {
                containerEl.innerHTML = this.errorState(e.message);
            }
        }
    },

    /**
     * Render a widget from cached data, or defer until it enters the viewport.
     */
    _lazyRenderWhenVisible(containerEl, json) {
        if (this._renderObservers?.has(containerEl)) return;

        const rect = containerEl.getBoundingClientRect();
        const isVisible =
            rect.top < window.innerHeight + 100 && rect.bottom > -100;

        if (isVisible) {
            this.render(containerEl, json);
            return;
        }

        if (!this._renderObservers) this._renderObservers = new Map();

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    observer.unobserve(containerEl);
                    this._renderObservers.delete(containerEl);
                    const data = this._widgetData.get(containerEl);
                    if (data) this.render(containerEl, data);
                }
            },
            { rootMargin: "100px" },
        );

        this._renderObservers.set(containerEl, observer);
        observer.observe(containerEl);
    },

    /**
     * Force-render a widget immediately from cached data, regardless of viewport.
     * Used by pop-out to ensure the chart exists before moving its canvas.
     */
    flushRender(containerEl) {
        if (containerEl.querySelector("canvas")) return; // already rendered

        const observer = this._renderObservers?.get(containerEl);
        if (observer) {
            observer.unobserve(containerEl);
            this._renderObservers.delete(containerEl);
        }

        const json = this._widgetData.get(containerEl);
        if (json) this.render(containerEl, json);
    },

    /**
     * Main render dispatcher.
     */
    render(containerEl, json) {
        const { widget_type, source_type, calculation_type, data, controls } =
            json;
        if (controls) {
            if (source_type) controls.source_type = source_type;
            if (widget_type) controls.widget_type = widget_type;
            if (calculation_type) controls.calculation_type = calculation_type;
        }
        this._widgetData.set(containerEl, json);
        containerEl.innerHTML = "";
        this._renderWidget(widget_type, containerEl, data, controls);
    },

    // ─── Loading / Error States ───

    loadingSkeleton() {
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    <svg class="animate-spin h-6 w-6 text-gray-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-xs text-gray-400 mt-2">Loading...</p>
                </div>
            </div>`;
    },

    emptyAssetState() {
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    <svg class="w-6 h-6 mx-auto text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-amber-500 mt-2 font-medium">No Assets Available</p>
                    <p class="text-xs text-gray-400 mt-1">The selected asset group has no assets for this widget's channel.</p>
                </div>
            </div>`;
    },

    errorState(message) {
        let displayMessage =
            message || "An error occurred while loading widget data.";
        if (
            displayMessage.includes("SQLSTATE") ||
            displayMessage.includes("syntax error") ||
            displayMessage.includes("500 Internal Server Error") ||
            displayMessage.includes("Server error:")
        ) {
            displayMessage =
                "Unable to display widget data. Please check widget metrics and asset configuration.";
        }
        const icon =
            '<svg class="w-6 h-6 mx-auto text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    ${icon}
                    <p class="text-xs text-red-400 mt-2">${this.escapeHtml(displayMessage)}</p>
                    <button onclick="this.closest('[x-ref]') ? null : location.reload()" class="text-xs text-primary-500 hover:underline mt-1">Retry</button>
                </div>
            </div>`;
    },

    accessRestrictedState() {
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    <svg class="w-6 h-6 mx-auto text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9.364-7.364A9 9 0 1112 3a9 9 0 017.364 4.636z"/>
                    </svg>
                    <p class="text-xs text-amber-500 mt-2 font-medium">Access Restricted</p>
                    <p class="text-xs text-gray-400 mt-1">You don't have permission to view this widget's data.</p>
                    <p class="text-xs text-gray-500 mt-1">Contact your project owner or editor to request access.</p>
                </div>
            </div>`;
    },

    isRetryableError(err) {
        const msg = (err && err.message) || "";
        if (!msg) return false;
        const patterns = [
            /cURL error 28/i,
            /Operation timed out/i,
            /connect\(\) timed out/i,
            /Connection timed out/i,
            /timed out after/i,
            /Request Timeout/i,
            /Gateway Timeout/i,
            /Cloudflare/i,
            /A Timeout Occurred/i,
            /504 Gateway Time-out/i,
            /502 Bad Gateway/i,
            /520 Origin Error/i,
            /521 Web Server Is Down/i,
            /522 Connection timed out/i,
            /523 Origin Is Unreachable/i,
            /524 A timeout occurred/i,
            /525 SSL handshake failed/i,
        ];
        return patterns.some((re) => re.test(msg));
    },

    cloudflareTimeoutState(message) {
        const displayMessage =
            message ||
            "This widget needs a bit more time to load its data.";
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    <svg class="w-6 h-6 mx-auto text-amber-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-xs text-amber-500 mt-2 font-medium">Still Loading — Auto-Retrying</p>
                    <p class="text-xs text-gray-400 mt-1">${this.escapeHtml(displayMessage)}</p>
                    <p class="text-xs text-gray-500 mt-1">This widget will automatically reload in 2 minutes.</p>
                    <button onclick="this.closest('.widget-content')?.dispatchEvent(new CustomEvent('widget-reload-now', { bubbles: true }))"
                            class="text-xs text-primary-500 hover:underline mt-1">Retry Now</button>
                </div>
            </div>`;
    },

    _scheduleRetry(widgetId, containerEl, controls, tenant) {
        this._cancelRetry(widgetId);

        const fire = () => {
            const pending = this._retryTimers.get(widgetId);
            if (!pending) return;
            // Only reload if the container is still in the DOM
            if (!containerEl.isConnected) {
                this._retryTimers.delete(widgetId);
                return;
            }
            this._retryTimers.delete(widgetId);
            containerEl.innerHTML = this.loadingSkeleton();
            this.renderWidget(widgetId, containerEl, controls, tenant);
        };

        const onManualRetry = () => fire();

        containerEl.addEventListener("widget-reload-now", onManualRetry);

        const token = {
            widgetId,
            containerEl,
            controls,
            tenant,
            timer: setTimeout(fire, 2 * 60 * 1000), // 2 minutes
            onManualRetry,
        };
        this._retryTimers.set(widgetId, token);
    },

    _cancelRetry(widgetId) {
        const pending = this._retryTimers.get(widgetId);
        if (!pending) return;
        if (pending.timer) clearTimeout(pending.timer);
        if (pending.containerEl && pending.onManualRetry) {
            pending.containerEl.removeEventListener(
                "widget-reload-now",
                pending.onManualRetry,
            );
        }
        this._retryTimers.delete(widgetId);
    },

    escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str;
        return div.innerHTML;
    },

    resolveMetricAxisDirection(metricKey, seriesIdx = 0, controls = null) {
        const mKey = String(metricKey || "").toLowerCase();
        // 1. Check custom user configuration in controls
        if (controls) {
            let userDir = null;
            // Check series_metric_namings
            if (controls.series_metric_namings?.[seriesIdx]?.[metricKey]?.axis_direction) {
                userDir = controls.series_metric_namings[seriesIdx][metricKey].axis_direction;
            } else if (controls.series_metric_namings?.[String(seriesIdx)]?.[metricKey]?.axis_direction) {
                userDir = controls.series_metric_namings[String(seriesIdx)][metricKey].axis_direction;
            } else if (controls.raw_series?.[seriesIdx]?.metric_namings?.[metricKey]?.axis_direction) {
                userDir = controls.raw_series[seriesIdx].metric_namings[metricKey].axis_direction;
            }

            if (userDir === "inverted") return true;
            if (userDir === "normal") return false;
        }

        // 2. Default fallback: Auto by metric convention
        return (
            mKey.includes("position") ||
            mKey.includes("bounce_rate") ||
            mKey.includes("bouncerate")
        );
    },

    // ─── Tile ───

    renderTile(containerEl, data, controls) {
        const resultFormat = this.getKpiResultFormat(controls);

        let value = data?.value ?? data?.current ?? 0;
        const previous = data?.previous ?? null;
        const prefix = data?.prefix ?? resultFormat?.prefix ?? "";
        const suffix = data?.suffix ?? resultFormat?.suffix ?? "";
        const label = data?.label ?? resultFormat?.label ?? "";
        let format = data?.format ?? resultFormat?.format ?? "number";

        if (
            format === "percentage" &&
            !data?.percentage &&
            resultFormat?.multiply
        ) {
            value = value * resultFormat.multiply;
        }

        let trendHtml = "";
        let changePercent = null;

        const lowerIsBetter =
            data?.lower_is_better ?? resultFormat?.lower_is_better ?? false;

        if (previous !== null && previous !== 0) {
            changePercent = ((value - previous) / Math.abs(previous)) * 100;
            const improved = lowerIsBetter
                ? changePercent <= 0
                : changePercent >= 0;
            const arrow = improved ? "▲" : "▼";
            const color = improved ? "text-green-500" : "text-red-500";
            trendHtml = `<span class="${color} text-sm font-medium ml-2">${arrow} ${Math.abs(changePercent).toFixed(1)}%</span>`;
        }

        const formattedValue =
            format === "currency"
                ? this.formatCurrency(value)
                : format === "percentage"
                  ? value.toFixed(1) + "%"
                  : this.formatNumber(value);

        containerEl.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full p-4">
                ${label ? `<p class="text-xs text-gray-500 dark:text-gray-400 mb-1">${this.escapeHtml(label)}</p>` : ""}
                <div class="flex items-baseline">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">${prefix}${formattedValue}${suffix}</span>
                    ${trendHtml}
                </div>
                ${changePercent !== null ? `<p class="text-xs text-gray-400 mt-1">vs previous period</p>` : ""}
            </div>`;
    },

    // ─── Line Chart ───

    renderLineChart(containerEl, data, controls) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];
        const resultFormatRaw = this.getKpiResultFormat(controls);
        const isPrimaryPosition =
            controls?.metrics?.[0] === "position" ||
            datasets[0]?.metric === "position" ||
            datasets[0]?.metric_key === "position" ||
            (datasets[0]?.key && String(datasets[0].key).includes("position"));
        const isPrimaryBounceRate =
            controls?.metrics?.[0] === "bounce_rate" ||
            controls?.metrics?.[0] === "bouncerate" ||
            datasets[0]?.metric === "bounce_rate" ||
            datasets[0]?.metric === "bouncerate" ||
            datasets[0]?.metric_key === "bounce_rate" ||
            datasets[0]?.metric_key === "bouncerate" ||
            (datasets[0]?.key && String(datasets[0].key).includes("bounce_rate"));
        const primaryMetricKey = controls?.metrics?.[0] || datasets[0]?.metric || datasets[0]?.metric_key || datasets[0]?.key || "";
        const primaryIsReverse = this.resolveMetricAxisDirection(primaryMetricKey, 0, controls);
        const reverseY = primaryIsReverse || !!controls?.reverse_y || !!data?.reverse_y || !!data?.scales?.y?.reverse;
        const resultFormat = isPrimaryPosition && resultFormatRaw?.format === "percentage"
            ? null
            : resultFormatRaw;

        const yMetric = controls?.metrics?.[0];
        const yFmt =
            resultFormat ||
            (yMetric ? this.METRIC_FORMATS[yMetric] || null : null);
        const yUnit =
            yFmt?.format === "percentage"
                ? "%"
                : yFmt?.format === "currency"
                  ? yFmt?.prefix || "$"
                  : yFmt?.suffix || "";
        const yAxisLabel =
            (yFmt?.label || "Value") + (yUnit ? " (" + yUnit + ")" : "");
        const yMetricName = this.getMetricName(yMetric);

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const isDatasetPosition = (ds, idx) => {
            const m = String(ds.metric || ds.metric_key || ds.key || "").toLowerCase();
            return m.includes("position") || (idx === 0 && isPrimaryPosition);
        };
        const isDatasetBounceRate = (ds, idx) => {
            const m = String(ds.metric || ds.metric_key || ds.key || "").toLowerCase();
            return m.includes("bounce_rate") || m.includes("bouncerate") || (idx === 0 && isPrimaryBounceRate);
        };
        const isDatasetReverse = (ds, idx) => {
            const m = ds.metric || ds.metric_key || ds.key || controls?.metrics?.[idx] || "";
            return this.resolveMetricAxisDirection(m, idx, controls);
        };

        const mappedDatasets = datasets.map((ds, idx) => {
            const isPos = isDatasetPosition(ds, idx);
            return {
                ...ds,
                currency: isPos
                    ? false
                    : (ds.currency ?? (resultFormat?.format === "currency" ? true : undefined)),
                percentage: isPos
                    ? false
                    : (ds.percentage ?? (resultFormat?.format === "percentage" ? true : undefined)),
                pointRadius: 6,
                pointHoverRadius: 10,
                pointHitRadius: 15,
                pointBackgroundColor:
                    ds.borderColor || ds.backgroundColor || "#3B82F6",
                pointBorderColor: ds.borderColor || ds.backgroundColor || "#3B82F6",
                pointBorderWidth: 2,
                pointHoverBorderWidth: 2,
            };
        });

        let chartScales = {
            x: {
                grid: { display: false },
                ticks: { maxTicksLimit: 8, font: { size: 10 } },
            },
        };

        if (datasets.length === 1) {
            const backendY = data?.scales?.y || {};
            const isPos = isPrimaryPosition;
            const isReverseScale = reverseY || isPos || isPrimaryBounceRate || !!backendY.reverse;
            chartScales.y = {
                beginAtZero: !isReverseScale && backendY.beginAtZero !== false,
                reverse: isReverseScale,
                title: {
                    display: true,
                    text: backendY.title?.text || yAxisLabel,
                },
                ticks: { font: { size: 10 }, ...backendY.ticks },
            };
            if (backendY.min !== undefined) {
                chartScales.y.min = backendY.min;
            } else if (isPos) {
                chartScales.y.min = 1;
            }
            if (backendY.suggestedMin !== undefined) {
                chartScales.y.suggestedMin = backendY.suggestedMin;
            } else if (isPos) {
                chartScales.y.suggestedMin = 1;
            }
            if (backendY.max !== undefined) {
                chartScales.y.max = backendY.max;
            }
            if (backendY.suggestedMax !== undefined) {
                chartScales.y.suggestedMax = backendY.suggestedMax;
            }
            mappedDatasets[0].yAxisID = "y";
        } else {
            if (data?.scales) {
                for (const [axisId, axisConf] of Object.entries(data.scales)) {
                    const isPosAxis = axisId.toLowerCase().includes("position");
                    const isBounceAxis = axisId.toLowerCase().includes("bounce_rate") || axisId.toLowerCase().includes("bouncerate");
                    const isRevAxis = isPosAxis || isBounceAxis || !!axisConf.reverse;
                    chartScales[axisId] = {
                        ...axisConf,
                        reverse: isRevAxis ? true : (axisConf.reverse ?? false),
                        beginAtZero: isRevAxis ? false : (axisConf.beginAtZero ?? true),
                        title: { display: false },
                        ticks: { display: false },
                    };
                    if (isPosAxis && chartScales[axisId].min === undefined) {
                        chartScales[axisId].min = 1;
                    }
                }
            } else {
                mappedDatasets.forEach((ds, idx) => {
                    if (ds.yAxisID) {
                        const isPosAxis = isDatasetPosition(ds, idx);
                        const isRevAxis = isDatasetReverse(ds, idx);
                        chartScales[ds.yAxisID] = {
                            type: "linear",
                            display: true,
                            reverse: isRevAxis,
                            beginAtZero: !isRevAxis,
                            title: { display: false },
                            ticks: { display: false },
                            grid: { drawOnChartArea: idx === 0 },
                        };
                        if (isPosAxis) {
                            chartScales[ds.yAxisID].min = 1;
                        }
                    }
                });
            }
        }

        const config = {
            type: "line",
            data: { labels, datasets: mappedDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover(e) {
                    const found = e.chart.getElementsAtEventForMode(
                        e,
                        "nearest",
                        { intersect: true },
                        false,
                    );
                    e.native.target.style.cursor = found.length
                        ? "pointer"
                        : "default";
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (ctx) =>
                                ctx[0]?.chart.data.labels?.[ctx[0].dataIndex] ||
                                "",
                            label: (ctx) => {
                                const dsLabel =
                                    ctx.dataset.label || yMetricName;
                                let val = ctx.parsed.y;
                                if (ctx.dataset?.percentage)
                                    return val.toFixed(1) + "% " + dsLabel;
                                if (ctx.dataset?.currency)
                                    return (
                                        this.formatCurrency(val) + " " + dsLabel
                                    );
                                const valY = this.formatMetricValue(
                                    val,
                                    yMetric,
                                );
                                return valY + " " + dsLabel;
                            },
                        },
                    },
                },
                scales: chartScales,
                elements: { line: { tension: 0.3 } },
            },
        };
        this._setAnimation(config, true);
        this.renderChart(containerEl, config);
    },

    // ─── Bar Chart ───

    renderBarChart(containerEl, data, controls) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];
        const isPrimaryPosition =
            controls?.metrics?.[0] === "position" ||
            datasets[0]?.metric === "position" ||
            datasets[0]?.metric_key === "position" ||
            (datasets[0]?.key && String(datasets[0].key).includes("position"));
        const isPrimaryBounceRate =
            controls?.metrics?.[0] === "bounce_rate" ||
            controls?.metrics?.[0] === "bouncerate" ||
            datasets[0]?.metric === "bounce_rate" ||
            datasets[0]?.metric === "bouncerate" ||
            datasets[0]?.metric_key === "bounce_rate" ||
            datasets[0]?.metric_key === "bouncerate" ||
            (datasets[0]?.key && String(datasets[0].key).includes("bounce_rate"));
        const primaryMetricKey = controls?.metrics?.[0] || datasets[0]?.metric || datasets[0]?.metric_key || datasets[0]?.key || "";
        const primaryIsReverse = this.resolveMetricAxisDirection(primaryMetricKey, 0, controls);
        const reverseY = primaryIsReverse || !!controls?.reverse_y || !!data?.reverse_y || !!data?.scales?.y?.reverse;

        const resultFormat = this.getKpiResultFormat(controls);

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const isDatasetPosition = (ds, idx) => {
            const m = String(ds.metric || ds.metric_key || ds.key || "").toLowerCase();
            return m.includes("position") || (idx === 0 && isPrimaryPosition);
        };
        const isDatasetBounceRate = (ds, idx) => {
            const m = String(ds.metric || ds.metric_key || ds.key || "").toLowerCase();
            return m.includes("bounce_rate") || m.includes("bouncerate") || (idx === 0 && isPrimaryBounceRate);
        };
        const isDatasetReverse = (ds, idx) => {
            const m = ds.metric || ds.metric_key || ds.key || controls?.metrics?.[idx] || "";
            return this.resolveMetricAxisDirection(m, idx, controls);
        };

        const mappedDatasets = datasets.map((ds) => ({
            ...ds,
            currency:
                ds.currency ??
                (resultFormat?.format === "currency" ? true : undefined),
            percentage:
                ds.percentage ??
                (resultFormat?.format === "percentage" ? true : undefined),
        }));

        let chartScales = {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        };

        if (datasets.length === 1) {
            const backendY = data?.scales?.y || {};
            const isPos = isPrimaryPosition;
            const isReverseScale = reverseY || isPos || isPrimaryBounceRate || !!backendY.reverse;
            chartScales.y = {
                beginAtZero: !isReverseScale && backendY.beginAtZero !== false,
                reverse: isReverseScale,
                title: { display: true, text: backendY.title?.text || "" },
                ticks: { font: { size: 10 }, ...backendY.ticks },
            };
            if (backendY.min !== undefined) {
                chartScales.y.min = backendY.min;
            } else if (isPos) {
                chartScales.y.min = 1;
            }
            if (backendY.suggestedMin !== undefined) {
                chartScales.y.suggestedMin = backendY.suggestedMin;
            } else if (isPos) {
                chartScales.y.suggestedMin = 1;
            }
            if (backendY.max !== undefined) {
                chartScales.y.max = backendY.max;
            }
            if (backendY.suggestedMax !== undefined) {
                chartScales.y.suggestedMax = backendY.suggestedMax;
            }
            mappedDatasets[0].yAxisID = "y";
        } else {
            if (data?.scales) {
                for (const [axisId, axisConf] of Object.entries(data.scales)) {
                    const isPosAxis = axisId.toLowerCase().includes("position");
                    const isBounceAxis = axisId.toLowerCase().includes("bounce_rate") || axisId.toLowerCase().includes("bouncerate");
                    const isRevAxis = isPosAxis || isBounceAxis || !!axisConf.reverse;
                    chartScales[axisId] = {
                        ...axisConf,
                        reverse: isRevAxis ? true : (axisConf.reverse ?? false),
                        beginAtZero: isRevAxis ? false : (axisConf.beginAtZero ?? true),
                        title: { display: false },
                        ticks: { display: false },
                    };
                    if (isPosAxis && chartScales[axisId].min === undefined) {
                        chartScales[axisId].min = 1;
                    }
                }
            } else {
                mappedDatasets.forEach((ds, idx) => {
                    if (ds.yAxisID) {
                        const isPosAxis = isDatasetPosition(ds, idx);
                        const isRevAxis = isDatasetReverse(ds, idx);
                        chartScales[ds.yAxisID] = {
                            type: "linear",
                            display: true,
                            reverse: isRevAxis,
                            beginAtZero: !isRevAxis,
                            title: { display: false },
                            ticks: { display: false },
                            grid: { drawOnChartArea: idx === 0 },
                        };
                        if (isPosAxis) {
                            chartScales[ds.yAxisID].min = 1;
                        }
                    }
                });
            }
        }

        const config = {
            type: "bar",
            data: { labels, datasets: mappedDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                let val = ctx.parsed.y;
                                if (!ctx.dataset?.percentage && resultFormat?.multiply)
                                    val = val * resultFormat.multiply;
                                if (ctx.dataset?.currency)
                                    return this.formatCurrency(val);
                                if (ctx.dataset?.percentage)
                                    return val.toFixed(1) + "%";
                                return this.formatNumber(val);
                            },
                        },
                    },
                },
                scales: chartScales,
            },
        };
        this._setAnimation(config, false);
        this.renderChart(containerEl, config);
    },

    // ─── Pie & Donut Chart ───

    renderPieChart(containerEl, data, controls) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];
        const resultFormat = this.getKpiResultFormat(controls);

        if (!labels.length || !datasets.length || !datasets[0]?.data?.length) {
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const pieStyle = data?.pie_style || controls?.pie_style || "donut";
        const isDonut = pieStyle !== "pie";
        const total = (datasets[0].data || []).reduce((acc, curr) => acc + (typeof curr === "number" ? curr : 0), 0);

        const mappedDatasets = datasets.map((ds) => ({
            ...ds,
            currency: ds.currency ?? (resultFormat?.format === "currency" ? true : undefined),
            percentage: ds.percentage ?? (resultFormat?.format === "percentage" ? true : undefined),
            borderWidth: 2,
            borderColor: document.documentElement.classList.contains("dark") ? "#1f2937" : "#ffffff",
        }));

        const isCurrency = mappedDatasets[0]?.currency;
        const isPercentage = mappedDatasets[0]?.percentage;

        // Custom plugin to draw center text in Donut charts
        const centerTextPlugin = {
            id: "centerTextPlugin_" + Math.random().toString(36).substr(2, 9),
            beforeDraw: (chart) => {
                if (!isDonut) return;
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                const centerX = (chartArea.left + chartArea.right) / 2;
                const centerY = (chartArea.top + chartArea.bottom) / 2;

                ctx.save();
                const isDark = document.documentElement.classList.contains("dark");
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";

                // Subtitle: Total
                ctx.font = "600 11px Inter, system-ui, sans-serif";
                ctx.fillStyle = isDark ? "#9ca3af" : "#6b7280";
                ctx.fillText("TOTAL", centerX, centerY - 10);

                // Value
                let formattedTotal = this.formatNumber(total);
                if (isCurrency) {
                    formattedTotal = this.formatCurrency(total);
                } else if (isPercentage) {
                    formattedTotal = total.toFixed(1) + "%";
                }
                ctx.font = "700 16px Inter, system-ui, sans-serif";
                ctx.fillStyle = isDark ? "#f3f4f6" : "#111827";
                ctx.fillText(formattedTotal, centerX, centerY + 10);
                ctx.restore();
            },
        };

        const config = {
            type: isDonut ? "doughnut" : "pie",
            data: { labels, datasets: mappedDatasets },
            plugins: isDonut ? [centerTextPlugin] : [],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: isDonut ? "65%" : "0%",
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: false,
                        external: (ctx) => this._externalTooltipHandler(ctx),
                    },
                },
            },
        };

        this._setAnimation(config, false);
        this.renderChart(containerEl, config);
    },

    // ─── Table ───

    renderTable(containerEl, data, controls) {
        const columns = data?.columns ?? [];
        const rows = data?.rows ?? [];

        if (!columns.length || !rows.length) {
            containerEl.style.padding = "1rem";
            containerEl.style.overflow = "";
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        containerEl.style.padding = "0";
        containerEl.style.overflow = "hidden";
        containerEl._tableData = data;

        if (!containerEl._tableSort) {
            // Backend may declare the intended default sort (e.g. breakdown "Rank Top By"
            // configured on a custom metric). Use it instead of sorting by the left column.
            const defaultSortColumn = data?.default_sort_column;
            const defaultSortDirection = data?.default_sort_direction || "desc";
            if (
                defaultSortColumn &&
                columns.some(
                    (c) =>
                        (c && c.key === defaultSortColumn) ||
                        c === defaultSortColumn,
                )
            ) {
                containerEl._tableSort = {
                    column: defaultSortColumn,
                    direction: defaultSortDirection,
                };
            } else {
                // Default sort by first column: DESC for time-based, ASC for dimensions
                const firstCol = columns[0];
                const firstKey = firstCol?.key || firstCol;
                const timeKeys = [
                    "date",
                    "daily",
                    "weekly",
                    "monthly",
                    "quarterly",
                    "semiannual",
                    "annually",
                ];
                const isTimeBased = timeKeys.includes(firstKey);
                containerEl._tableSort = {
                    column: firstKey,
                    direction: isTimeBased ? "desc" : "asc",
                };
            }
        }
        const sort = containerEl._tableSort;

        let sortedRows = [...rows];
        if (sort.column) {
            const colDef = columns.find(
                (c) => c.key === sort.column || c === sort.column,
            );
            const isNumeric =
                colDef?.format === "number" ||
                colDef?.format === "currency" ||
                colDef?.format === "percentage";
            sortedRows.sort((a, b) => {
                let va = a[sort.column],
                    vb = b[sort.column];
                if (isNumeric) {
                    va = Number(va) || 0;
                    vb = Number(vb) || 0;
                } else {
                    va = String(va).toLowerCase();
                    vb = String(vb).toLowerCase();
                }
                return va < vb
                    ? sort.direction === "asc"
                        ? -1
                        : 1
                    : va > vb
                      ? sort.direction === "asc"
                          ? 1
                          : -1
                      : 0;
            });
        }

        const blockFirstCol =
            controls.block_first_col !== undefined
                ? !!controls.block_first_col
                : true;

        let html =
            '<div class="table-outer-wrap" style="display:flex;width:100%;height:100%;border-radius:inherit;overflow:hidden;position:relative;">';

        if (blockFirstCol) {
            // Fixed First Column Table (capped at 50% width of the table container)
            const firstCol = columns[0];
            const firstKey = firstCol.key || firstCol;
            const firstRawLabel = firstCol.label || firstCol;
            const firstDisplayLabel =
                this.getMetricName(firstRawLabel) || firstRawLabel;
            const firstIsActive = sort.column === firstKey;
            const firstArrow = firstIsActive
                ? sort.direction === "asc"
                    ? " \u25B2"
                    : " \u25BC"
                : "";

            html +=
                '<div class="fixed-col-wrap" style="flex:0 1 auto;max-width:50%;min-width:0;z-index:2;overflow:hidden;" class="border-r border-gray-200 dark:border-gray-700">';
            html +=
                '<table style="width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0;">';
            html += '<thead style="position:sticky;top:0;z-index:2;">';
            html += `<tr class="bg-gray-50 dark:bg-gray-800"><th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-200 border-r border-gray-200 dark:border-gray-700 data-has-tooltip" data-sort-key="${firstKey}" data-tooltip="${this.escapeHtml(firstDisplayLabel)}" title="${this.escapeHtml(firstDisplayLabel)}" style="min-width:100px;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${this.escapeHtml(firstDisplayLabel)}<span class="sort-arrow" style="font-size:10px;margin-left:2px;">${firstArrow}</span></th></tr></thead>`;
            html += '<tbody class="bg-white dark:bg-gray-900">';
            sortedRows.forEach((row, ri) => {
                const isEven = ri % 2 === 0;
                const cellBgClass = isEven
                    ? "bg-white dark:bg-gray-900"
                    : "bg-gray-50 dark:bg-gray-800";
                const val = row[firstKey] ?? row[firstCol] ?? "";
                const valStr = String(val);
                html += `<tr class="${cellBgClass} border-t border-gray-200 dark:border-gray-800"><td class="px-3 py-2 text-gray-700 dark:text-gray-200 ${cellBgClass} border-r border-gray-200 dark:border-gray-700 data-has-tooltip" data-tooltip="${this.escapeHtml(valStr)}" title="${this.escapeHtml(valStr)}" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:100px;max-width:100%;">${this.escapeHtml(valStr)}</td></tr>`;
            });
            html += "</tbody></table></div>";
        }

        // Scrollable Columns Table (all columns if !blockFirstCol, columns.slice(1) if blockFirstCol)
        const scrollCols = blockFirstCol ? columns.slice(1) : columns;
        html +=
            '<div class="table-scroll-wrap" style="flex:1 1 auto;overflow:auto;height:100%;min-width:0;">';
        html +=
            '<table style="width:100%;border-collapse:separate;border-spacing:0;">';
        html += '<thead style="position:sticky;top:0;z-index:1;">';
        html += '<tr class="bg-gray-50 dark:bg-gray-800">';
        scrollCols.forEach((col, cIdx) => {
            const key = col.key || col;
            const rawLabel = col.label || col;
            const displayLabel = this.getMetricName(rawLabel) || rawLabel;
            const isActive = sort.column === key;
            const isNumeric =
                col.format === "currency" ||
                col.format === "percentage" ||
                col.format === "number";
            const arrow = isActive
                ? sort.direction === "asc"
                    ? " \u25B2"
                    : " \u25BC"
                : "";
            const thAlign = isNumeric ? "text-right" : "text-left";
            const isFirstOfAll = !blockFirstCol && cIdx === 0;
            const thStyle = isFirstOfAll
                ? "max-width:50%;min-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                : (isNumeric
                    ? "min-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                    : "min-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;");
            html += `<th class="px-3 py-2 ${thAlign} font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-200 data-has-tooltip" data-sort-key="${key}" data-tooltip="${this.escapeHtml(displayLabel)}" title="${this.escapeHtml(displayLabel)}" style="${thStyle}">${this.escapeHtml(displayLabel)}<span class="sort-arrow" style="font-size:10px;margin-left:2px;">${arrow}</span></th>`;
        });
        html += "</tr></thead>";

        html += '<tbody class="bg-white dark:bg-gray-900">';
        sortedRows.forEach((row, ri) => {
            const isEven = ri % 2 === 0;
            const cellBgClass = isEven
                ? "bg-white dark:bg-gray-900"
                : "bg-gray-50 dark:bg-gray-800";
            html += `<tr class="${cellBgClass} border-t border-gray-200 dark:border-gray-800">`;
            scrollCols.forEach((col, cIdx) => {
                const key = col.key || col;
                const val = row[key] ?? row[col] ?? "";
                const isNumeric =
                    col.format === "currency" ||
                    col.format === "percentage" ||
                    col.format === "number";
                const formatted =
                    isNumeric && col.format === "currency"
                        ? this.formatCurrency(val)
                        : isNumeric && col.format === "percentage"
                          ? val != null
                              ? Number(val).toFixed(1) + "%"
                              : ""
                          : isNumeric && col.format === "number"
                            ? this._formatTableNumber(val)
                            : val;
                const tdClass = isNumeric
                    ? "px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-200 text-right"
                    : "px-3 py-2 text-gray-700 dark:text-gray-200";
                const isFirstOfAll = !blockFirstCol && cIdx === 0;
                const tdStyle = isFirstOfAll
                    ? "max-width:50%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    : "overflow:hidden;text-overflow:ellipsis;white-space:nowrap;";
                const valStr = String(val);
                html += `<td class="${tdClass} ${cellBgClass} data-has-tooltip" data-tooltip="${this.escapeHtml(valStr)}" title="${this.escapeHtml(valStr)}" style="${tdStyle}">${this.escapeHtml(String(formatted))}</td>`;
            });
            html += "</tr>";
        });
        html += "</tbody></table></div></div>";

        if (data.total !== undefined) {
            html += `<div class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">${this._formatTableNumber(data.total)} results</div>`;
        }

        containerEl.innerHTML = html;

        if (blockFirstCol) {
            const scrollWrap = containerEl.querySelector(".table-scroll-wrap");
            const fixedWrap = containerEl.querySelector(".fixed-col-wrap");
            if (scrollWrap && fixedWrap) {
                scrollWrap.addEventListener("scroll", () => {
                    fixedWrap.scrollTop = scrollWrap.scrollTop;
                });
            }
        }

        // Attach custom interactive tooltip for truncated cells and headers
        this._attachTableTruncationTooltips(containerEl);

        containerEl.querySelectorAll("th[data-sort-key]").forEach((th) => {
            th.addEventListener("click", () => {
                const key = th.dataset.sortKey;
                if (sort.column === key) {
                    sort.direction = sort.direction === "asc" ? "desc" : "asc";
                } else {
                    sort.column = key;
                    sort.direction = "asc";
                }
                this.renderTable(containerEl, data, controls);
            });
        });
    },

    _attachTableTruncationTooltips(containerEl) {
        let activeTooltip = null;

        const removeTooltip = () => {
            if (activeTooltip) {
                activeTooltip.remove();
                activeTooltip = null;
            }
        };

        const targets = containerEl.querySelectorAll(".data-has-tooltip");
        targets.forEach((el) => {
            el.addEventListener("mouseenter", () => {
                // Check if text content is actually truncated
                if (el.scrollWidth > el.clientWidth + 1) {
                    removeTooltip();
                    const text = el.dataset.tooltip || el.getAttribute("title") || el.textContent;
                    if (!text || !text.trim()) return;

                    const tip = document.createElement("div");
                    tip.className = "dashboard-cell-tooltip";
                    tip.textContent = text.trim();
                    tip.style.cssText = `
                        position: fixed;
                        z-index: 99999;
                        max-width: 420px;
                        word-break: break-all;
                        white-space: normal;
                        background-color: #1f2937;
                        color: #f9fafb;
                        font-size: 11px;
                        line-height: 1.4;
                        font-weight: 500;
                        padding: 6px 10px;
                        border-radius: 6px;
                        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.25), 0 4px 6px -2px rgba(0,0,0,0.1);
                        pointer-events: none;
                        opacity: 0;
                        transition: opacity 0.12s ease-in-out;
                    `;

                    document.body.appendChild(tip);
                    activeTooltip = tip;

                    const rect = el.getBoundingClientRect();
                    const tipRect = tip.getBoundingClientRect();

                    // Calculate top/bottom placement
                    let top = rect.top - tipRect.height - 6;
                    if (top < 8) {
                        top = rect.bottom + 6;
                    }

                    // Calculate left placement
                    let left = rect.left;
                    if (left + tipRect.width > window.innerWidth - 12) {
                        left = Math.max(8, window.innerWidth - tipRect.width - 12);
                    }

                    tip.style.top = `${top}px`;
                    tip.style.left = `${left}px`;
                    tip.style.opacity = "1";
                }
            });

            el.addEventListener("mouseleave", removeTooltip);
        });

        // Also clean up tooltip if the container or page scrolls
        containerEl.addEventListener("scroll", removeTooltip, { capture: true, passive: true });
    },

    // ─── Gauge ───

    renderGauge(containerEl, data, controls) {
        const widgetId = containerEl.dataset.widgetId;
        if (widgetId && this._chartInstances.has(widgetId)) {
            this._chartInstances.get(widgetId).destroy();
            this._chartInstances.delete(widgetId);
        }

        const resultFormat = this.getKpiResultFormat(controls);

        let value = data?.value ?? 0;
        const min = data?.min ?? 0;
        const max = data?.max ?? 100;
        const label = data?.label ?? "";
        const thresholds = data?.thresholds ?? [
            { from: 0, to: 33, color: "#EF4444" },
            { from: 33, to: 66, color: "#F59E0B" },
            { from: 66, to: 100, color: "#22C55E" },
        ];

        const metricKey = controls?.metrics?.[0];
        const isPercentageMetric =
            metricKey === "ctr" ||
            metricKey === "bounce_rate" ||
            metricKey === "result_rate" ||
            resultFormat?.format === "percentage";

        let rawValue = data?.value ?? 0;

        // If backend returned a percentage metric as a whole percentage number (>1 e.g. 2.31 for 2.31%), normalize it to decimal ratio (0.0231)
        if (isPercentageMetric && rawValue > 1.0) {
            rawValue = rawValue / 100;
        }

        // Target max normalization: if metric is percentage and max is whole percentage (>1 e.g. 100 or 6.7), normalize fill comparison
        let fillValue = isPercentageMetric ? rawValue * 100 : rawValue;
        let effectiveMax = max;
        if (isPercentageMetric && max <= 1.0) {
            effectiveMax = max * 100;
        }

        const pct = Math.min(
            100,
            Math.max(0, ((fillValue - min) / (effectiveMax - min)) * 100),
        );

        let color = thresholds[0]?.color ?? "#22C55E";
        for (const t of thresholds) {
            if (pct >= t.from && pct <= t.to) {
                color = t.color;
                break;
            }
        }

        const remainingPct = 100 - pct;
        const isDark = document.documentElement.classList.contains("dark");
        const trackColor = isDark ? "#374151" : "#E5E7EB";

        const displayLabel =
            data?.label || (metricKey ? this.getMetricName(metricKey) : "");

        const formattedRawVal = metricKey
            ? this.formatMetricValue(rawValue, metricKey)
            : isPercentageMetric
              ? (rawValue * 100).toFixed(1) + "%"
              : resultFormat?.format === "currency"
                ? this.formatCurrency(rawValue)
                : this.formatNumber(rawValue);
        const formattedMaxVal = isPercentageMetric
            ? effectiveMax.toFixed(1) + "%"
            : metricKey
              ? this.formatMetricValue(max, metricKey)
              : resultFormat?.format === "currency"
                ? this.formatCurrency(max)
                : this.formatNumber(max);

        const formattedValueStr = `${formattedRawVal} / ${formattedMaxVal}`;

        containerEl.innerHTML = `
            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:12px; box-sizing:border-box; position:relative; overflow:hidden;">
                <div style="position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
                    <canvas style="width:100%; height:100%; display:block;"></canvas>
                </div>
            </div>`;

        const createChart = () => {
            const canvas = containerEl.querySelector("canvas");
            if (!canvas) return;
            const ctx = canvas.getContext("2d");

            const gaugeTextPlugin = {
                id: "gaugeCenterText",
                afterDraw: (chart) => {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;

                    const meta = chart.getDatasetMeta(0);
                    if (!meta || !meta.data || !meta.data[0]) return;

                    // The inner arc center and radius of the doughnut chart
                    const arc = meta.data[0];
                    const centerX = arc.x;
                    const centerY = arc.y; // The horizontal baseline of the 180° gauge arc
                    const innerRadius = arc.innerRadius || 60;

                    ctx.save();
                    ctx.textAlign = "center";
                    ctx.textBaseline = "bottom";

                    // Dynamically calculate font sizes proportional to chart size
                    const pctFontSize = Math.max(
                        16,
                        Math.min(32, Math.round(innerRadius * 0.35)),
                    );
                    const labelFontSize = Math.max(
                        10,
                        Math.min(16, Math.round(innerRadius * 0.16)),
                    );
                    const valFontSize = Math.max(
                        10,
                        Math.min(16, Math.round(innerRadius * 0.15)),
                    );

                    const isDark =
                        document.documentElement.classList.contains("dark");

                    // Draw formatted percentage (top line)
                    ctx.fillStyle = isDark ? "#FFFFFF" : "#111827";
                    ctx.font = `bold ${pctFontSize}px system-ui, -apple-system, sans-serif`;
                    ctx.fillText(
                        `${Math.round(pct)}%`,
                        centerX,
                        centerY - innerRadius * 0.45,
                    );

                    // Draw label (middle line)
                    if (displayLabel) {
                        ctx.fillStyle = isDark ? "#9CA3AF" : "#6B7280";
                        ctx.font = `600 ${labelFontSize}px system-ui, -apple-system, sans-serif`;
                        ctx.fillText(
                            displayLabel,
                            centerX,
                            centerY - innerRadius * 0.22,
                        );
                    }

                    // Draw value / max (bottom line directly resting on the baseline)
                    ctx.fillStyle = isDark ? "#6B7280" : "#9CA3AF";
                    ctx.font = `500 ${valFontSize}px system-ui, -apple-system, sans-serif`;
                    ctx.fillText(
                        formattedValueStr,
                        centerX,
                        centerY - innerRadius * 0.04,
                    );

                    ctx.restore();
                },
            };

            const chart = new Chart(ctx, {
                type: "doughnut",
                data: {
                    labels: ["Current Value", "Target Remaining"],
                    datasets: [
                        {
                            data: [pct, remainingPct],
                            backgroundColor: [color, trackColor],
                            borderWidth: 0,
                            borderRadius: 4,
                        },
                    ],
                },
                plugins: [gaugeTextPlugin],
                options: {
                    rotation: 270,
                    circumference: 180,
                    cutout: "70%",
                    responsive: true,
                    maintainAspectRatio: false,
                    onHover(e) {
                        const found = e.chart.getElementsAtEventForMode(
                            e,
                            "nearest",
                            { intersect: true },
                            false,
                        );
                        e.native.target.style.cursor = found.length
                            ? "pointer"
                            : "default";
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (tooltipItem) => {
                                    if (tooltipItem.dataIndex === 0) {
                                        return `Current Value: ${formattedRawVal} (${Math.round(pct)}%)`;
                                    } else {
                                        const remainingRaw = Math.max(
                                            0,
                                            max - rawValue,
                                        );
                                        const formattedRem = metricKey
                                            ? this.formatMetricValue(
                                                  remainingRaw,
                                                  metricKey,
                                              )
                                            : this.formatNumber(remainingRaw);
                                        return `Target Max: ${formattedMaxVal} (Remaining: ${formattedRem})`;
                                    }
                                },
                            },
                        },
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: false,
                        duration: 800,
                    },
                },
            });

            if (widgetId) {
                this._chartInstances.set(widgetId, chart);
            }
        };

        if (typeof Chart === "undefined") {
            const script = document.createElement("script");
            script.src =
                "https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js";
            script.onload = createChart;
            document.head.appendChild(script);
        } else {
            createChart();
        }
    },

    // ─── Sparkline ───

    renderSparkline(containerEl, data, controls) {
        const points = data?.points ?? data?.values ?? [];

        if (!points.length) {
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data</div>';
            return;
        }

        const metricKey = controls?.metrics?.[0];
        const metricFmt = metricKey ? this.METRIC_FORMATS[metricKey] : null;
        const lowerIsBetter = metricFmt?.lower_is_better ?? false;

        const current = points[points.length - 1];
        const first = points[0];
        const isUp = lowerIsBetter ? current < first : current >= first;
        const trend = isUp ? "up" : "down";
        const color = trend === "up" ? "#22C55E" : "#EF4444";

        const min = Math.min(...points);
        const max = Math.max(...points);
        const range = max - min || 1;
        const w = 160,
            h = 40;
        const stepX = w / (points.length - 1);

        const pathD = points
            .map((p, i) => {
                const x = i * stepX;
                const y = lowerIsBetter
                    ? ((p - min) / range) * h
                    : h - ((p - min) / range) * h;
                return (
                    (i === 0 ? "M" : "L") + x.toFixed(1) + " " + y.toFixed(1)
                );
            })
            .join(" ");

        const areaD = pathD + " L" + w + " " + h + " L0 " + h + " Z";

        let changePct = 0;
        if (first !== 0)
            changePct = ((current - first) / Math.abs(first)) * 100;

        const gradientId =
            "spark-fill-" + Math.random().toString(36).substr(2, 9);

        containerEl.innerHTML = `
            <div class="flex items-stretch gap-3 p-4 h-full">
                <div class="flex-shrink-0 text-right self-center flex flex-col">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">${this.formatNumber(current)}</span>
                    <span class="text-xs ${trend === "up" ? "text-green-500" : "text-red-500"}">${trend === "up" ? "▲" : "▼"} ${Math.abs(changePct).toFixed(0)}%</span>
                </div>
                <svg viewBox="0 0 ${w} ${h}" class="flex-1 h-full" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="${gradientId}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="${color}" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="${color}" stop-opacity="0.02"/>
                        </linearGradient>
                    </defs>
                    <path d="${areaD}" fill="url(#${gradientId})"/>
                    <path d="${pathD}" fill="none" stroke="${color}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>`;
    },

    // ─── Anomaly List ───

    renderAnomalyList(containerEl, data) {
        const anomalies = data?.anomalies ?? data?.points ?? [];

        if (!anomalies.length) {
            containerEl.innerHTML =
                '<div class="flex items-center justify-center h-full text-sm text-green-500 p-4">✓ No anomalies detected</div>';
            return;
        }

        let html =
            '<div class="overflow-auto max-h-full divide-y divide-gray-200 dark:divide-gray-700">';
        anomalies.slice(0, 20).forEach((a) => {
            const severity = a.severity ?? a.anomaly_score ?? 0;
            const severityLabel =
                severity >= 0.9
                    ? "Critical"
                    : severity >= 0.7
                      ? "High"
                      : severity >= 0.4
                        ? "Medium"
                        : "Low";
            const color =
                severity >= 0.9
                    ? "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300"
                    : severity >= 0.7
                      ? "bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300"
                      : severity >= 0.4
                        ? "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300"
                        : "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";

            html += `<div class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">${this.escapeHtml(a.label || a.name || "Anomaly")}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded-full font-medium ${color}">${severityLabel}</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span>Value: ${this.formatNumber(a.value ?? a.actual_value)}</span>
                    <span>Expected: ${this.formatNumber(a.expected ?? a.expected_value)}</span>
                </div>
                ${a.date ? `<p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">${this.escapeHtml(a.date)}</p>` : ""}
            </div>`;
        });
        html += "</div>";
        if (anomalies.length > 20) {
            html += `<div class="px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700">+${anomalies.length - 20} more</div>`;
        }

        containerEl.innerHTML = html;
    },

    // ─── Anomaly Chart ───

    renderAnomalyChart(containerEl, data, controls) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];
        const anomalyDates = data?.anomaly_dates ?? [];
        const reverseY =
            controls?.metrics?.[0] === "position" ||
            controls?.metrics?.[0] === "bounce_rate" ||
            controls?.metrics?.[0] === "bouncerate" ||
            datasets[0]?.metric === "position" ||
            datasets[0]?.metric === "bounce_rate" ||
            datasets[0]?.metric === "bouncerate" ||
            !!controls?.reverse_y ||
            !!data?.reverse_y;

        const resultFormat = this.getKpiResultFormat(controls);

        const yMetric = controls?.metrics?.[0];
        const yFmt =
            resultFormat ||
            (yMetric ? this.METRIC_FORMATS[yMetric] || null : null);
        const yUnit =
            yFmt?.format === "percentage"
                ? "%"
                : yFmt?.format === "currency"
                  ? yFmt?.prefix || "$"
                  : yFmt?.suffix || "";
        const yAxisLabel =
            (yFmt?.label || "Value") + (yUnit ? " (" + yUnit + ")" : "");
        const yMetricName = this.getMetricName(yMetric);

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const anomalySet = new Set(anomalyDates);

        const anomalyPlugin = {
            id: "anomalyLines",
            afterDraw(chart) {
                const ctx = chart.ctx;
                const xScale = chart.scales.x;

                ctx.save();
                ctx.setLineDash([4, 4]);
                ctx.lineWidth = 1.5;
                ctx.strokeStyle = "#EF4444";

                labels.forEach((label, i) => {
                    if (anomalySet.has(label)) {
                        const x = xScale.getPixelForValue(i);
                        const y0 = chart.chartArea.top;
                        const y1 = chart.chartArea.bottom;

                        ctx.beginPath();
                        ctx.moveTo(x, y0);
                        ctx.lineTo(x, y1);
                        ctx.stroke();
                    }
                });

                ctx.restore();
            },
        };

        const mappedDatasets = datasets.map((ds) => ({
            ...ds,
            currency:
                ds.currency ??
                (resultFormat?.format === "currency" ? true : undefined),
            percentage:
                ds.percentage ??
                (resultFormat?.format === "percentage" ? true : undefined),
            pointRadius: 6,
            pointHoverRadius: 10,
            pointHitRadius: 15,
            pointBackgroundColor:
                ds.borderColor || ds.backgroundColor || "#3B82F6",
            pointBorderColor: ds.borderColor || ds.backgroundColor || "#3B82F6",
            pointBorderWidth: 2,
            pointHoverBorderWidth: 2,
        }));

        const config = {
            type: "line",
            data: { labels, datasets: mappedDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover(e) {
                    const found = e.chart.getElementsAtEventForMode(
                        e,
                        "nearest",
                        { intersect: true },
                        false,
                    );
                    e.native.target.style.cursor = found.length
                        ? "pointer"
                        : "default";
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (ctx) =>
                                ctx[0]?.chart.data.labels?.[ctx[0].dataIndex] ||
                                "",
                            label: (ctx) => {
                                const dsLabel =
                                    ctx.dataset.label || yMetricName;
                                let val = ctx.parsed.y;
                                if (ctx.dataset?.percentage)
                                    return val.toFixed(1) + "% " + dsLabel;
                                if (ctx.dataset?.currency)
                                    return (
                                        this.formatCurrency(val) + " " + dsLabel
                                    );
                                const valY = this.formatMetricValue(
                                    val,
                                    yMetric,
                                );
                                return valY + " " + dsLabel;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 10, font: { size: 10 } },
                    },
                    y: {
                        beginAtZero: !reverseY,
                        reverse: reverseY,
                        title: { display: true, text: yAxisLabel },
                        ticks: {
                            font: { size: 10 },
                            callback(val) {
                                if (val >= 1000000)
                                    return (val / 1000000).toFixed(1) + "M";
                                if (val >= 1000)
                                    return (val / 1000).toFixed(1) + "K";
                                return val;
                            },
                        },
                    },
                },
            },
            plugins: [anomalyPlugin],
        };
        this._setAnimation(config, true);
        this.renderChart(containerEl, config);
    },

    // ─── Scatter Plot ───

    renderScatterPlot(containerEl, data, controls) {
        if (!data || !data.datasets || !data.datasets.length) {
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const higherIsWorse = [
            "position",
            "bounce_rate",
            "bouncerate",
            "cpc",
            "cpa",
            "cost_per_click",
        ];
        const reverseYColor = higherIsWorse.includes(controls?.metrics?.[0]);
        const reverseXColor = higherIsWorse.includes(controls?.metrics?.[1]);
        const yMetric = controls?.metrics?.[0] || "";
        const xMetric = controls?.metrics?.[1] || "";
        const customReverseY = this.resolveMetricAxisDirection(yMetric, 0, controls);
        const customReverseX = this.resolveMetricAxisDirection(xMetric, 1, controls);

        const reverseYAxis =
            controls?.reverse_y ||
            data?.reverse_y ||
            customReverseY;
        const reverseXAxis =
            controls?.reverse_x ||
            data?.reverse_x ||
            customReverseX;

        const resultFormat = this.getKpiResultFormat(controls);
        const xFmt = controls?.metrics?.[1]
            ? this.METRIC_FORMATS[controls.metrics[1]] || null
            : null;
        const yFmt =
            resultFormat ||
            (controls?.metrics?.[0]
                ? this.METRIC_FORMATS[controls.metrics[0]] || null
                : null);

        /* console.log('[ScatterPlot] controls:', controls);
        console.log('[ScatterPlot] metrics:', controls?.metrics);
        console.log('[ScatterPlot] resultFormat:', resultFormat);
        console.log('[ScatterPlot] xFmt:', xFmt, '| yFmt:', yFmt); */

        const formatPoint = (v, fmt, axis) => {
            if (!fmt) {
                const r = v.toLocaleString("en-US", {
                    maximumFractionDigits: 2,
                });
                /* if (v === 0 || (v > 0 && v < 1)) console.log(`[formatPoint][${axis}] v=${v}, fmt=null →`, r); */
                return r;
            }
            let val = fmt.multiply ? v * fmt.multiply : v;
            let r;
            if (fmt.format === "currency")
                r =
                    "$" +
                    val.toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
            else if (fmt.format === "percentage") r = val.toFixed(1) + "%";
            else r = val.toLocaleString("en-US", { maximumFractionDigits: 2 });
            /* console.log(`[formatPoint][${axis}] v=${v}, fmt=${JSON.stringify(fmt)}, val=${val} →`, r); */
            return r;
        };

        const formatUnit = (fmt) => {
            if (!fmt) return "";
            if (fmt.format === "percentage") return "%";
            if (fmt.format === "currency") return fmt.prefix || "$";
            if (fmt.suffix) return fmt.suffix;
            return "";
        };
        const yUnit = formatUnit(yFmt);
        const xUnit = formatUnit(xFmt);
        const yAxisLabel =
            (yFmt?.label || "Value") + (yUnit ? " (" + yUnit + ")" : "");
        const xAxisLabel =
            (xFmt?.label || controls?.metrics?.[1] || "X") +
            (xUnit ? " (" + xUnit + ")" : "");

        const scatterDataset = data.datasets.find((d) => d.type === "scatter");
        if (
            scatterDataset &&
            scatterDataset.data &&
            scatterDataset.data.length > 0
        ) {
            const yValues = scatterDataset.data.map((p) => p.y);
            const minY = Math.min(...yValues);
            const maxY = Math.max(...yValues);

            const bgColors = [];
            const borderColors = [];

            scatterDataset.data.forEach((p) => {
                let t = 0.5;
                if (maxY > minY) {
                    t = (p.y - minY) / (maxY - minY);
                }
                if (reverseYColor) {
                    t = 1 - t;
                }
                const hue = t * 120; // 0 is red, 120 is green
                bgColors.push(`hsla(${hue}, 80%, 50%, 0.7)`);
                borderColors.push(`hsla(${hue}, 80%, 45%, 1)`);
            });

            scatterDataset.backgroundColor = bgColors;
            scatterDataset.borderColor = borderColors;
            scatterDataset.borderWidth = 1;
        }

        const yScaleOpts = {
            type: "linear",
            title: { display: true, text: yAxisLabel },
            reverse: reverseYAxis,
            ticks: { callback: (v) => formatPoint(v, yFmt, "Y") },
        };

        const xMetricName = this.getMetricName(controls?.metrics?.[1]);
        const yMetricName = this.getMetricName(controls?.metrics?.[0]);

        // Extract R2 (coefficient of determination) if present in payload data
        const r2Raw =
            data?.r2 ??
            data?.r_squared ??
            data?.rSquared ??
            data?.regression?.r2 ??
            data?.stats?.r2 ??
            data?.summary?.r2 ??
            data?.datasets?.find(
                (d) => d.r2 !== undefined || d.r_squared !== undefined,
            )?.r2 ??
            data?.datasets?.find(
                (d) => d.r2 !== undefined || d.r_squared !== undefined,
            )?.r_squared;
        const r2 =
            r2Raw !== null && r2Raw !== undefined && !isNaN(Number(r2Raw))
                ? Number(r2Raw)
                : null;

        let r2Color = "#3B82F6"; // Default Blue
        let fitRating = "Moderate Fit";

        if (r2 !== null) {
            if (r2 >= 0.7) {
                r2Color = "#10B981"; // Green / High Explanatory Power
                fitRating = "Strong Fit";
            } else if (r2 >= 0.4) {
                r2Color = "#F59E0B"; // Amber / Moderate Explanatory Power
                fitRating = "Moderate Fit";
            } else {
                r2Color = "#EF4444"; // Red / Low Explanatory Power
                fitRating = "Weak Fit";
            }
        }

        const mappedData = JSON.parse(JSON.stringify(data));
        if (mappedData.datasets) {
            mappedData.datasets = mappedData.datasets.map((ds) => {
                if (ds.type === "line") {
                    return {
                        ...ds,
                        borderColor: r2Color,
                        borderWidth: 2.5,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHitRadius: 20,
                    };
                }
                return {
                    ...ds,
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    pointHitRadius: 15,
                    pointBorderWidth: 2,
                    pointHoverBorderWidth: 2,
                };
            });
        }

        const config = {
            type: "scatter",
            data: mappedData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover(e) {
                    const found = e.chart.getElementsAtEventForMode(
                        e,
                        "nearest",
                        { intersect: true },
                        false,
                    );
                    e.native.target.style.cursor = found.length
                        ? "pointer"
                        : "default";
                },
                scales: {
                    x: {
                        type: "linear",
                        position: "bottom",
                        title: { display: true, text: xAxisLabel },
                        reverse: reverseXAxis,
                        ticks: {
                            callback: (v) => formatPoint(v, xFmt, "X"),
                        },
                    },
                    y: yScaleOpts,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const ds = ctx.dataset;
                                if (ds.type === "line") {
                                    const point = ctx.raw;
                                    const valX = this.formatMetricValue(
                                        point.x,
                                        controls?.metrics?.[1],
                                    );
                                    const valY = this.formatMetricValue(
                                        point.y,
                                        controls?.metrics?.[0],
                                    );
                                    const r2Label =
                                        r2 !== null
                                            ? ` — R² = ${r2.toFixed(3)} (${fitRating})`
                                            : "";
                                    return `Trendline: (${valX} ${xMetricName}, ${valY} ${yMetricName})${r2Label}`;
                                }
                                const point = ctx.raw;
                                const valX = this.formatMetricValue(
                                    point.x,
                                    controls?.metrics?.[1],
                                );
                                const valY = this.formatMetricValue(
                                    point.y,
                                    controls?.metrics?.[0],
                                );
                                const baseLabel = point.label
                                    ? point.label + " — "
                                    : "";
                                return (
                                    baseLabel +
                                    "(" +
                                    valX +
                                    " " +
                                    xMetricName +
                                    ", " +
                                    valY +
                                    " " +
                                    yMetricName +
                                    ")"
                                );
                            },
                        },
                    },
                },
            },
        };

        // Prepare header badge HTML if R2 exists
        let badgeHtml = "";
        if (r2 !== null) {
            const badgePct = (r2 * 100).toFixed(1);
            let pillClasses =
                "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700/50";
            if (fitRating === "Moderate Fit") {
                pillClasses =
                    "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700/50";
            } else if (fitRating === "Weak Fit") {
                pillClasses =
                    "bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700/50";
            }

            badgeHtml = `
                <div class="r2-summary-badge flex items-center justify-between px-3 py-1.5 rounded-lg bg-gray-50 text-gray-800 dark:bg-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 text-xs select-none mb-2 flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-600 dark:text-gray-400">Model Fit (R²):</span>
                        <span class="font-semibold text-gray-900 dark:text-white">${r2.toFixed(3)} (${badgePct}% variance)</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider ${pillClasses}">${fitRating}</span>
                </div>`;
        }

        containerEl.style.display = "flex";
        containerEl.style.flexDirection = "column";
        containerEl.style.width = "100%";
        containerEl.style.height = "100%";
        containerEl.style.overflow = "hidden";
        containerEl.innerHTML =
            badgeHtml +
            '<div class="chart-canvas-wrap flex-1 min-h-0 relative w-full h-full"></div>';

        const hasLineDs = mappedData.datasets.some((ds) => ds.type === "line");
        if (hasLineDs) {
            config.options = config.options || {};
            config.options.animation = {
                x: {
                    type: "number",
                    easing: "easeOutQuart",
                    duration: 1000,
                    from(ctx) {
                        if (ctx.type !== "data" || ctx.mode !== "default")
                            return undefined;
                        const ds = ctx.chart.data.datasets[ctx.datasetIndex];
                        if (ds?.type === "line") return NaN;
                        return ctx.raw?.x;
                    },
                },
                y: {
                    type: "number",
                    easing: "easeOutQuart",
                    duration: 1000,
                    from(ctx) {
                        if (ctx.type !== "data" || ctx.mode !== "default")
                            return undefined;
                        const ds = ctx.chart.data.datasets[ctx.datasetIndex];
                        if (ds?.type === "line") return 0;
                        return ctx.raw?.y;
                    },
                },
            };
        }

        // Constrain axes to scatter data only (ignore trend line endpoints),
        // with 10 % breathing room so edge points aren't clipped by the grid border.
        // Remove ticks and grid lines in the grace area via afterBuildTicks.
        let scatterPoints = mappedData.datasets?.find(
            (d) => d.type === "scatter",
        )?.data;
        if (scatterPoints && scatterPoints.length > 0) {
            const xs = scatterPoints.map((p) => p.x);
            const ys = scatterPoints.map((p) => p.y);
            const dataMinX = Math.min(...xs),
                dataMaxX = Math.max(...xs);
            const dataMinY = Math.min(...ys),
                dataMaxY = Math.max(...ys);
            let xMin = dataMinX,
                xMax = dataMaxX;
            let yMin = dataMinY,
                yMax = dataMaxY;
            const xRange = xMax - xMin || 1;
            const yRange = yMax - yMin || 1;
            xMin -= xRange * 0.1;
            xMax += xRange * 0.1;
            yMin -= yRange * 0.1;
            yMax += yRange * 0.1;
            config.options.scales.x.min = xMin;
            config.options.scales.x.max = xMax;
            config.options.scales.x.afterBuildTicks = (axis) => {
                // When zoomed (axis range differs from initial), let Chart.js auto-generate ticks
                if (
                    Math.abs(axis.min - xMin) > 0.001 ||
                    Math.abs(axis.max - xMax) > 0.001
                )
                    return;
                const filtered = axis.ticks.filter(
                    (t) => t.value >= dataMinX && t.value <= dataMaxX,
                );
                const values = filtered.map((t) => t.value);
                if (values.length > 0) {
                    const maxVal = Math.max(...values);
                    if (maxVal < dataMaxX) filtered.push({ value: dataMaxX });
                    const minVal = Math.min(...values);
                    if (minVal > dataMinX) filtered.push({ value: dataMinX });
                }
                axis.ticks = filtered;
            };
            config.options.scales.y.min = yMin;
            config.options.scales.y.max = yMax;
            config.options.scales.y.afterBuildTicks = (axis) => {
                // When zoomed (axis range differs from initial), let Chart.js auto-generate ticks
                if (
                    Math.abs(axis.min - yMin) > 0.001 ||
                    Math.abs(axis.max - yMax) > 0.001
                )
                    return;
                const filtered = axis.ticks.filter(
                    (t) => t.value >= dataMinY && t.value <= dataMaxY,
                );
                const values = filtered.map((t) => t.value);
                // Keep the 0 reference tick if it falls within the visible axis range (Bug 1 fix)
                if (0 >= yMin && 0 <= yMax && !values.includes(0)) {
                    filtered.push({ value: 0 });
                    values.push(0);
                }
                if (values.length > 0) {
                    const maxVal = Math.max(...values);
                    if (maxVal < dataMaxY) filtered.push({ value: dataMaxY });
                    const minVal = Math.min(...values);
                    if (minVal > dataMinY) filtered.push({ value: dataMinY });
                }
                axis.ticks = filtered;
            };
        }

        // Split cluster point ([[[others]]]) into its own hidden dataset for show/hide toggle
        const mainScatterDs = mappedData.datasets?.find(
            (d) => d.type === "scatter",
        );
        let clusterPoint = null;
        if (mainScatterDs) {
            const ci = mainScatterDs.data.findIndex((p) => p._isCluster);
            if (ci >= 0) {
                clusterPoint = mainScatterDs.data.splice(ci, 1)[0];
                delete clusterPoint._isCluster;
                mappedData.datasets.push({
                    type: "scatter",
                    label: "Clustered",
                    data: [clusterPoint],
                    backgroundColor: "rgba(107, 114, 128, 0.5)",
                    borderColor: "rgba(107, 114, 128, 1)",
                    borderWidth: 2,
                    pointRadius: 8,
                    pointHoverRadius: 14,
                });
            }
        }

        const canvasWrap = containerEl.querySelector(".chart-canvas-wrap");
        this.renderChart(canvasWrap, config);

        // Add show/hide toggle for the cluster point
        if (clusterPoint && mainScatterDs) {
            const toggleId =
                "cluster-toggle-" +
                (containerEl._clusterToggleCount =
                    (containerEl._clusterToggleCount || 0) + 1);
            const wrapper = document.createElement("div");
            wrapper.style.cssText =
                "display:flex;align-items:center;justify-content:center;gap:6px;margin-top:4px;";
            wrapper.innerHTML =
                '<label for="' +
                toggleId +
                '" style="font-size:11px;color:#9ca3af;cursor:pointer;user-select:none;">Show clustered</label>';
            const input = document.createElement("input");
            input.type = "checkbox";
            input.id = toggleId;
            input.checked = true;
            input.style.cssText = "cursor:pointer;accent-color:#6b7280;";
            wrapper.prepend(input);
            containerEl.appendChild(wrapper);

            input.addEventListener("change", () => {
                const chart = Chart.getChart(
                    containerEl.querySelector("canvas"),
                );
                if (!chart || !chart.data || !chart.data.datasets) return;
                const ds = chart.data.datasets.find(
                    (d) => d.type === "scatter" && d.label === "Clustered",
                );
                if (!ds) return;
                const meta = chart.getDatasetMeta(
                    chart.data.datasets.indexOf(ds),
                );
                meta.hidden = !input.checked;
                chart.update();
            });
        }
    },

    // ─── Combo Chart (Multi-Metric / Multi-Series & MACD) ───

    renderComboChart(containerEl, data, controls) {
        if (!data || !data.datasets || !data.datasets.length) {
            containerEl.innerHTML =
                '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const resultFormat = this.getKpiResultFormat(controls);
        const comboConfig =
            controls?.combo_chart_config || controls?.combo_series_config || {};

        // Rate, ratio, and percentage metric keys that default to Line on secondary axis (y1)
        const rateMetricKeywords = [
            "roas",
            "cpc",
            "cpm",
            "ctr",
            "rate",
            "aov",
            "frequency",
            "cost_per",
            "percentage",
            "ratio",
            "bounce",
        ];

        let hasLeftAxis = false;
        let hasRightAxis = false;
        let leftAxisUnit = "";
        let rightAxisUnit = "";
        let leftAxisReverse = false;
        let rightAxisReverse = false;

        const mappedDatasets = data.datasets.map((ds, idx) => {
            const labelLower = (ds.label || "").toLowerCase();
            let metricKey = ds.metric || ds.metric_key || "";
            let seriesIdx = ds.series_index;

            if (!metricKey && ds.key) {
                const mMatch = String(ds.key).match(/^series_(\d+)_(.+)$/);
                if (mMatch) {
                    if (seriesIdx === undefined || seriesIdx === null)
                        seriesIdx = parseInt(mMatch[1], 10);
                    metricKey = mMatch[2];
                } else {
                    metricKey = ds.key;
                }
            }
            if (seriesIdx === undefined || seriesIdx === null) {
                seriesIdx = idx;
            }

            const keyLower = String(metricKey).toLowerCase();

            // 1. Determine if this dataset is a rate / ratio or volume metric
            const isRateOrPercentage =
                ds.percentage ||
                resultFormat?.format === "percentage" ||
                rateMetricKeywords.some(
                    (kw) => labelLower.includes(kw) || keyLower.includes(kw),
                );

            const isCurrency =
                ds.currency ??
                (resultFormat?.format === "currency" ||
                    ["spend", "cost", "revenue", "cpc", "cpm", "aov"].some(
                        (kw) =>
                            labelLower.includes(kw) || keyLower.includes(kw),
                    ));

            // 2. Determine Chart Type (User override > Dataset default > Heuristic)
            const comboEntry =
                comboConfig[seriesIdx + "_" + metricKey] ||
                comboConfig[seriesIdx + "_" + keyLower] ||
                comboConfig[idx + "_" + metricKey] ||
                comboConfig[idx + "_" + keyLower] ||
                comboConfig[metricKey] ||
                comboConfig[keyLower] ||
                comboConfig[idx] ||
                comboConfig[ds.label] ||
                comboConfig[ds.key];

            let chartType = comboEntry?.type || null;

            if (!chartType) {
                if (ds.type) {
                    chartType = ds.type;
                } else {
                    const hasSpendOrCurrency = data.datasets.some((d) => {
                        const l = (d.label || "").toLowerCase();
                        const m = (
                            d.metric ||
                            d.metric_key ||
                            ""
                        ).toLowerCase();
                        return (
                            d.currency ||
                            ["spend", "cost", "revenue"].some(
                                (kw) => l.includes(kw) || m.includes(kw),
                            )
                        );
                    });

                    if (hasSpendOrCurrency) {
                        // When Spend/Cost/Revenue is present, make Spend the Bar, and non-currency counts/rates the Lines
                        const isThisCurrency =
                            isCurrency ||
                            ["spend", "cost", "revenue"].some(
                                (kw) =>
                                    labelLower.includes(kw) ||
                                    keyLower.includes(kw),
                            );
                        chartType = isThisCurrency ? "bar" : "line";
                    } else if (
                        isRateOrPercentage &&
                        !data.datasets.every((d) =>
                            rateMetricKeywords.some((kw) =>
                                (d.label || "").toLowerCase().includes(kw),
                            ),
                        )
                    ) {
                        // If mixed volumes and rates, rates are lines
                        chartType = "line";
                    } else if (data.datasets.length > 1 && idx > 0) {
                        // Fallback when no spend/currency: primary series is bar, others are lines
                        chartType = "line";
                    } else {
                        chartType = "bar";
                    }
                }
            }

            // 3. Determine Axis Assignment (User override > Default)
            let yAxisID = comboEntry?.axis || ds.yAxisID || null;
            if (!yAxisID) {
                if (comboConfig[idx]?.axis) {
                    yAxisID = comboConfig[idx].axis;
                } else if (comboConfig[ds.label]?.axis) {
                    yAxisID = comboConfig[ds.label].axis;
                } else {
                    const hasCurrencyInAny = data.datasets.some((d) => {
                        const l = (d.label || "").toLowerCase();
                        const m = (
                            d.metric ||
                            d.metric_key ||
                            ""
                        ).toLowerCase();
                        return (
                            d.currency ||
                            ["spend", "cost", "revenue"].some(
                                (kw) => l.includes(kw) || m.includes(kw),
                            )
                        );
                    });

                    if (hasCurrencyInAny) {
                        // Currency on left ('y'), count/rate lines on right ('y1')
                        const isThisCurrency =
                            isCurrency ||
                            ["spend", "cost", "revenue"].some(
                                (kw) =>
                                    labelLower.includes(kw) ||
                                    keyLower.includes(kw),
                            );
                        yAxisID = isThisCurrency ? "y" : "y1";
                    } else if (chartType === "line" && isRateOrPercentage) {
                        yAxisID = "y1";
                    } else {
                        yAxisID = "y";
                    }
                }
            }

            const dsMetricKey = ds.metric || ds.metric_key || ds.key || controls?.metrics?.[idx] || "";
            const isReverseForDs = this.resolveMetricAxisDirection(dsMetricKey, idx, controls);

            if (yAxisID === "y1") {
                hasRightAxis = true;
                if (isReverseForDs) rightAxisReverse = true;
                if (!rightAxisUnit) {
                    if (isRateOrPercentage) rightAxisUnit = "%";
                    else if (isCurrency) rightAxisUnit = "$";
                }
            } else {
                hasLeftAxis = true;
                if (isReverseForDs) leftAxisReverse = true;
                if (!leftAxisUnit) {
                    if (isCurrency) leftAxisUnit = "$";
                    else if (isRateOrPercentage) leftAxisUnit = "%";
                }
            }

            return {
                ...ds,
                type: chartType,
                yAxisID: yAxisID,
                currency: isCurrency,
                percentage: isRateOrPercentage,
                borderWidth:
                    chartType === "line"
                        ? ds.borderWidth || 2.5
                        : ds.borderWidth || 1,
                pointRadius:
                    chartType === "line" ? (ds.pointRadius ?? 4) : undefined,
                pointHoverRadius:
                    chartType === "line"
                        ? (ds.pointHoverRadius ?? 6)
                        : undefined,
                pointStyle: chartType === "line" ? "line" : "rectRounded",
                fill: ds.fill !== undefined ? ds.fill : false,
            };
        });

        const scalesConfig = {
            x: {
                grid: { display: false },
                ticks: { maxTicksLimit: 12, font: { size: 10 } },
            },
            y: {
                type: "linear",
                display: hasLeftAxis || !hasRightAxis,
                position: "left",
                reverse: leftAxisReverse,
                beginAtZero: !leftAxisReverse,
                grid: { color: "rgba(156, 163, 175, 0.15)" },
                ticks: {
                    font: { size: 10 },
                    callback: (val) => {
                        if (leftAxisUnit === "$")
                            return this.formatCurrency(val);
                        if (leftAxisUnit === "%") return val.toFixed(1) + "%";
                        if (val >= 1000000)
                            return (val / 1000000).toFixed(1) + "M";
                        if (val >= 1000) return (val / 1000).toFixed(1) + "K";
                        return this.formatNumber(val);
                    },
                },
            },
        };

        if (hasRightAxis) {
            scalesConfig.y1 = {
                type: "linear",
                display: true,
                position: "right",
                reverse: rightAxisReverse,
                beginAtZero: !rightAxisReverse,
                grid: { drawOnChartArea: false }, // Avoid duplicate gridlines
                ticks: {
                    font: { size: 10 },
                    callback: (val) => {
                        if (rightAxisUnit === "$")
                            return this.formatCurrency(val);
                        if (rightAxisUnit === "%") return val.toFixed(1) + "%";
                        if (val >= 1000000)
                            return (val / 1000000).toFixed(1) + "M";
                        if (val >= 1000) return (val / 1000).toFixed(1) + "K";
                        return this.formatNumber(val);
                    },
                },
            };
        }

        const config = {
            type: "bar",
            data: { ...data, datasets: mappedDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false,
                },
                scales: scalesConfig,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const ds = ctx.dataset || {};
                                const label = ds.label || "Value";
                                let val = ctx.parsed.y;
                                if (!ds.percentage && resultFormat?.multiply)
                                    val = val * resultFormat.multiply;
                                if (ds.currency)
                                    return `${label}: ${this.formatCurrency(val)}`;
                                if (ds.percentage)
                                    return `${label}: ${val.toFixed(1)}%`;
                                return `${label}: ${this.formatNumber(val)}`;
                            },
                        },
                    },
                },
            },
        };

        this._setAnimation(config, false);
        this.renderChart(containerEl, config);
    },

    // ─── Chart.js Helper ───

    _setAnimation(config, isLine) {
        if (isLine) {
            config.options = config.options || {};
            config.options.animation = {
                x: {
                    type: "number",
                    easing: "linear",
                    duration: 200,
                    from: NaN,
                    delay(ctx) {
                        if (ctx.type !== "data" || ctx.mode !== "default")
                            return 0;
                        const count =
                            ctx.chart.data.datasets[ctx.datasetIndex]?.data
                                ?.length || 1;
                        return (
                            ((1000 - 200) / (count - 1 || 1)) * ctx.dataIndex
                        );
                    },
                },
                y: {
                    type: "number",
                    easing: "easeOutQuart",
                    duration: 200,
                    from: (ctx) =>
                        ctx.type === "data" && ctx.mode === "default"
                            ? 0
                            : undefined,
                },
            };
        } else {
            config.options = config.options || {};
            config.options.animation = {
                duration: 200,
                easing: "easeOutQuart",
                delay(ctx) {
                    if (ctx.type !== "data" || ctx.mode !== "default") return 0;
                    const count = ctx.chart.data.labels?.length || 1;
                    return ((1000 - 200) / (count - 1 || 1)) * ctx.dataIndex;
                },
            };
        }
    },

    _chartJsPromise: null,
    _ensureChartJs(callback) {
        if (typeof window.Chart !== "undefined") {
            callback();
            return;
        }
        if (!this._chartJsPromise) {
            this._chartJsPromise = new Promise((resolve) => {
                const existing = document.querySelector(
                    'script[src*="chart.umd.min.js"]',
                );
                if (existing) {
                    existing.addEventListener("load", () => resolve(), {
                        once: true,
                    });
                    existing.addEventListener("error", () => resolve(), {
                        once: true,
                    });
                } else {
                    const script = document.createElement("script");
                    script.src =
                        "https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js";
                    script.onload = () => resolve();
                    script.onerror = () => resolve();
                    document.head.appendChild(script);
                }
            });
        }
        this._chartJsPromise.then(() => callback());
    },

    _zoomPluginPromise: null,
    _ensureZoomPlugin(callback) {
        this._ensureChartJs(() => {
            if (typeof window.Chart === "undefined") {
                callback();
                return;
            }
            if (
                window.Chart.registry &&
                window.Chart.registry.plugins &&
                window.Chart.registry.plugins.get("zoom")
            ) {
                callback();
                return;
            }
            if (!this._zoomPluginPromise) {
                this._zoomPluginPromise = new Promise((resolve) => {
                    const existing = document.querySelector(
                        'script[src*="chartjs-plugin-zoom"]',
                    );
                    if (existing) {
                        existing.addEventListener("load", () => resolve(), {
                            once: true,
                        });
                        existing.addEventListener("error", () => resolve(), {
                            once: true,
                        });
                    } else {
                        const script = document.createElement("script");
                        script.src =
                            "https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js";
                        script.onload = () => resolve();
                        script.onerror = () => resolve();
                        document.head.appendChild(script);
                    }
                });
            }
            this._zoomPluginPromise.then(() => callback());
        });
    },

    _themeObserverInitialized: false,
    _initThemeObserver() {
        if (this._themeObserverInitialized || typeof window === "undefined")
            return;
        this._themeObserverInitialized = true;

        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                if (m.type === "attributes" && m.attributeName === "class") {
                    this.updateTheme();
                    break;
                }
            }
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
        });
        window.addEventListener("theme-changed", () => this.updateTheme());
    },

    _crosshairInitialized: false,
    _isCrosshairActive: false,

    _lastMousePos: null,

    _initCrosshairListeners() {
        if (this._crosshairInitialized || typeof window === "undefined") return;
        this._crosshairInitialized = true;

        window.addEventListener(
            "mousemove",
            (e) => {
                this._lastMousePos = {
                    clientX: e.clientX,
                    clientY: e.clientY,
                    target: e.target,
                };
            },
            { passive: true },
        );

        const handleKey = (e, isDown) => {
            if (e.key !== "Control" && e.key !== "Shift") return;
            if (this._isCrosshairActive === isDown) return;
            this._isCrosshairActive = isDown;
            this._applyCrosshairModeToAllCharts();
        };

        window.addEventListener("keydown", (e) => handleKey(e, true));
        window.addEventListener("keyup", (e) => handleKey(e, false));
        window.addEventListener("blur", () => {
            if (this._isCrosshairActive) {
                this._isCrosshairActive = false;
                this._applyCrosshairModeToAllCharts();
            }
        });
    },

    _applyCrosshairModeToAllCharts() {
        if (!this._lastMousePos) return;
        const { clientX, clientY } = this._lastMousePos;
        const elem = document.elementFromPoint(clientX, clientY);
        if (elem && elem.tagName === "CANVAS") {
            const evt = new MouseEvent("mousemove", {
                clientX,
                clientY,
                bubbles: true,
                cancelable: true,
            });
            elem.dispatchEvent(evt);
        }
    },

    updateTheme() {
        const isDark = document.documentElement.classList.contains("dark");
        const legendColor = isDark ? "#E4E4E7" : "#374151";
        const tickColor = isDark ? "#A1A1AA" : "#71717A";
        const gridColor = isDark
            ? "rgba(255,255,255,0.05)"
            : "rgba(0,0,0,0.06)";

        this._chartInstances.forEach((chart) => {
            if (!chart || !chart.options) return;
            if (chart.options.scales) {
                for (const axis of Object.values(chart.options.scales)) {
                    if (axis.ticks) axis.ticks.color = tickColor;
                    if (axis.grid) axis.grid.color = gridColor;
                }
            }
            // Skipping native legend color — native legend is disabled
            try {
                chart.update("none");
            } catch (e) {
                // Ignore transient update errors during navigation
            }
        });

        // Update custom HTML legend colors
        this._updateCustomLegendTheme(isDark);
    },

    _updateCustomLegendTheme(isDark) {
        document.querySelectorAll(".custom-chart-legend").forEach((wrapper) => {
            const toggle = wrapper.querySelector(".chart-legend-toggle");
            if (toggle) {
                toggle.style.borderTopColor = isDark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.08)";
                toggle.style.color = isDark ? "#A1A1AA" : "#9CA3AF";
            }
            wrapper.querySelectorAll(".chart-legend-body > div").forEach((item) => {
                item.style.color = isDark ? "#D4D4D8" : "#374151";
            });
        });
    },

    renderChart(containerEl, config) {
        this._initThemeObserver();
        this._initCrosshairListeners();
        this._pinnedTooltips.delete(containerEl);
        const isDark = document.documentElement.classList.contains("dark");
        if (config.options && config.options.scales) {
            for (const axis of Object.values(config.options.scales)) {
                if (axis.ticks) {
                    axis.ticks.color = isDark ? "#A1A1AA" : "#71717A";
                }
                if (axis.grid) {
                    axis.grid.color = isDark
                        ? "rgba(255,255,255,0.05)"
                        : "rgba(0,0,0,0.06)";
                }
            }
        }
        // Native legend disabled; custom HTML legend handles theming

        config.options = config.options || {};
        config.options.interaction = config.options.interaction || {};
        config.options.interaction.mode = "nearest";
        config.options.interaction.axis = "x";
        config.options.interaction.intersect = false;

        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = config.options.plugins.tooltip || {};
        config.options.plugins.tooltip.mode = "nearest";
        config.options.plugins.tooltip.axis = "x";
        config.options.plugins.tooltip.intersect = false;
        config.options.plugins.tooltip.enabled = false;
        config.options.plugins.tooltip.external = (ctx) =>
            this._externalTooltipHandler(ctx);
        config.options.plugins.zoom = {
            zoom: {
                wheel: { enabled: true, speed: 0.05, modifierKey: "ctrl" },
                pinch: { enabled: true },
                mode: "xy",
            },
            pan: {
                enabled: true,
                mode: "xy",
                threshold: 10,
            },
        };

        const canvas = document.createElement("canvas");
        const existingCanvas = containerEl.querySelector("canvas");
        if (existingCanvas) existingCanvas.remove();
        // Remove any stale custom legend from a previous render
        const existingLegend = containerEl.querySelector(".custom-chart-legend");
        if (existingLegend) existingLegend.remove();

        // Ensure flex-column layout so legend sits below the canvas
        containerEl.style.display = "flex";
        containerEl.style.flexDirection = "column";
        containerEl.style.overflow = "hidden";
        canvas.style.flex = "1 1 0";
        canvas.style.minHeight = "0";

        containerEl.appendChild(canvas);

        const createChart = () => {
            const chart = new Chart(canvas, config);
            this._chartInstances.set(containerEl, chart);
            this._attachTooltipPin(chart, canvas, containerEl);
            canvas.addEventListener("dblclick", () => {
                if (chart.options?.plugins?.zoom) chart.resetZoom();
            });
            // Build custom HTML legend for multi-dataset charts or pie/donut charts
            if ((chart.data.datasets && chart.data.datasets.length > 1) || (['pie', 'doughnut'].includes(chart.config.type) && chart.data.labels?.length > 1)) {
                this._renderCustomLegend(chart, containerEl);
            }
        };

        this._ensureZoomPlugin(createChart);
    },

    /**
     * Build a custom HTML legend below the chart canvas.
     * - CSS Grid with equal-width columns sized to the longest label.
     * - Collapsible toggle bar showing series/slice count + chevron.
     * - Click items to toggle dataset/slice visibility.
     */
    _renderCustomLegend(chart, containerEl) {
        const datasets = chart.data.datasets || [];
        if (datasets.length === 0) return;
        const isPie = ['pie', 'doughnut'].includes(chart.config.type);
        const labels = chart.data.labels || [];
        const isDark = document.documentElement.classList.contains("dark");

        // ── Wrapper ──
        const wrapper = document.createElement("div");
        wrapper.className = "custom-chart-legend";
        wrapper.style.cssText = "width:100%; flex-shrink:0; user-select:none;";

        // ── Toggle bar ──
        const toggleBar = document.createElement("div");
        toggleBar.className = "chart-legend-toggle";
        toggleBar.style.cssText = [
            "display:flex", "align-items:center", "justify-content:space-between",
            "cursor:pointer", "font-size:11px", "padding:3px 6px",
            "border-top:1px solid " + (isDark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.08)"),
            "color:" + (isDark ? "#A1A1AA" : "#9CA3AF"),
            "transition:color 0.15s",
        ].join(";");
        const itemsList = isPie
            ? labels.map((l, i) => {
                const bg = Array.isArray(datasets[0]?.backgroundColor) ? datasets[0].backgroundColor[i] : '#888';
                return { label: l || `Slice ${i + 1}`, color: bg, index: i };
            })
            : datasets.map((ds, i) => {
                const isLine = ds.type === 'line';
                const swatchColor = ds.borderColor || ds.backgroundColor || '#888';
                return { label: ds.label || `Series ${i + 1}`, color: swatchColor, isLine, index: i, ds };
            });

        toggleBar.innerHTML =
            `<span>Legend (${itemsList.length})</span><span class="legend-chevron" style="font-size:10px;transition:transform 0.2s">▲</span>`;
        toggleBar.addEventListener("mouseenter", () => {
            toggleBar.style.color = isDark ? "#E4E4E7" : "#374151";
        });
        toggleBar.addEventListener("mouseleave", () => {
            toggleBar.style.color = isDark ? "#A1A1AA" : "#9CA3AF";
        });

        // ── Legend body (grid) ──
        const body = document.createElement("div");
        body.className = "chart-legend-body";

        // Calculate optimal min column width from longest label
        const maxLabelLen = Math.max(...itemsList.map((item) => (item.label || "").length));
        const minColWidth = Math.max(160, Math.min(320, maxLabelLen * 7.5 + 44));

        body.style.cssText = [
            "display:grid",
            `grid-template-columns:repeat(auto-fill, minmax(${minColWidth}px, 1fr))`,
            "gap:4px 10px",
            "padding:4px 6px 6px",
            "max-height:7.5rem",
            "overflow-y:auto",
            "transition:max-height 0.25s ease, opacity 0.2s ease, padding 0.25s ease",
        ].join(";");

        // ── Build items ──
        itemsList.forEach((it) => {
            const item = document.createElement("div");
            item.style.cssText = [
                "display:flex", "align-items:center", "gap:6px",
                "cursor:pointer", "padding:2px 4px", "border-radius:4px",
                "white-space:nowrap", "overflow:hidden", "text-overflow:ellipsis",
                "transition:opacity 0.15s, background 0.15s",
                "font-size:11px",
                "color:" + (isDark ? "#D4D4D8" : "#374151"),
            ].join(";");

            // Color swatch
            const swatch = document.createElement("span");
            if (it.isLine) {
                // Line swatch: short line
                swatch.style.cssText = [
                    "display:inline-block", "width:18px", "height:3px",
                    "border-radius:2px", "flex-shrink:0",
                    "background:" + it.color,
                ].join(";");
            } else {
                // Bar/slice swatch: small rounded rect
                swatch.style.cssText = [
                    "display:inline-block", "width:12px", "height:12px",
                    "border-radius:2px", "flex-shrink:0",
                    "background:" + it.color,
                    "border:1px solid " + (isDark ? "rgba(255,255,255,0.1)" : "rgba(0,0,0,0.1)"),
                ].join(";");
            }

            item.appendChild(swatch);

            // Channel / Brand Icon (if available or detected from label/dataset)
            const iconSvg = this.getChannelIconSvg(it.ds?.channel, it.ds?.dependency, it.label);
            if (iconSvg) {
                const iconContainer = document.createElement("span");
                iconContainer.className = "legend-channel-icon";
                iconContainer.style.cssText = "display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;opacity:0.75;";
                iconContainer.innerHTML = iconSvg;
                item.appendChild(iconContainer);
            }

            // Label
            const label = document.createElement("span");
            label.style.cssText = "overflow:hidden; text-overflow:ellipsis; white-space:nowrap;";
            label.textContent = it.label;
            label.title = it.label;

            item.appendChild(label);

            // Hover
            item.addEventListener("mouseenter", () => {
                item.style.background = isDark ? "rgba(255,255,255,0.06)" : "rgba(0,0,0,0.04)";
            });
            item.addEventListener("mouseleave", () => {
                item.style.background = "transparent";
            });

            // Click → toggle visibility
            item.addEventListener("click", () => {
                if (isPie) {
                    chart.toggleDataVisibility(it.index);
                    chart.update();
                    const isHidden = !chart.getDataVisibility(it.index);
                    item.style.opacity = isHidden ? "0.35" : "1";
                    label.style.textDecoration = isHidden ? "line-through" : "none";
                } else {
                    const meta = chart.getDatasetMeta(it.index);
                    meta.hidden = meta.hidden === null ? !chart.data.datasets[it.index].hidden : null;
                    chart.update();
                    const isHidden = meta.hidden;
                    item.style.opacity = isHidden ? "0.35" : "1";
                    label.style.textDecoration = isHidden ? "line-through" : "none";
                }
            });

            body.appendChild(item);
        });

        // ── Toggle collapse/expand ──
        let collapsed = isPie;
        const chevron = toggleBar.querySelector(".legend-chevron");
        if (collapsed) {
            chevron.textContent = "▼";
            body.style.maxHeight = "0";
            body.style.opacity = "0";
            body.style.paddingTop = "0";
            body.style.paddingBottom = "0";
            body.style.overflow = "hidden";
        }
        toggleBar.addEventListener("click", () => {
            collapsed = !collapsed;
            if (collapsed) {
                body.style.maxHeight = "0";
                body.style.opacity = "0";
                body.style.paddingTop = "0";
                body.style.paddingBottom = "0";
                body.style.overflow = "hidden";
                chevron.textContent = "▼";
            } else {
                body.style.maxHeight = "7.5rem";
                body.style.opacity = "1";
                body.style.paddingTop = "4px";
                body.style.paddingBottom = "6px";
                body.style.overflow = "auto";
                chevron.textContent = "▲";
            }
            // Let Chart.js reclaim/release the space
            requestAnimationFrame(() => chart.resize());
        });

        wrapper.appendChild(toggleBar);
        wrapper.appendChild(body);
        containerEl.appendChild(wrapper);
    },

    _attachTooltipPin(chart, canvas, containerEl) {
        canvas.addEventListener("click", (e) => {
            // Resolve container from canvas current parent — handles popOut/popIn
            const container = canvas.parentNode || containerEl;
            const pinned = this._pinnedTooltips.get(container);
            if (pinned) {
                this._pinnedTooltips.delete(container);
                const tooltipEl = container.querySelector(".chart-tooltip");
                if (tooltipEl) {
                    tooltipEl.style.pointerEvents = "none";
                    tooltipEl.style.opacity = "0";
                    tooltipEl.innerHTML = "";
                }
                return;
            }
            const mode = this._isCrosshairActive ? "index" : "nearest";
            const intersect = !this._isCrosshairActive;
            let elements = chart.getElementsAtEventForMode(
                e,
                mode,
                { axis: "x", intersect },
                false,
            );
            if (elements.length === 0) return;

            if (
                this._isCrosshairActive &&
                (chart.config.type === "line" || chart.config.type === "bar")
            ) {
                const targetIdx = elements[0].index;
                const synthesized = [];
                chart.data.datasets.forEach((ds, dsIndex) => {
                    const meta = chart.getDatasetMeta(dsIndex);
                    if (meta.hidden || ds.hidden) return;
                    if (ds.data[targetIdx] !== undefined && ds.data[targetIdx] !== null) {
                        synthesized.push({
                            element: meta.data?.[targetIdx],
                            datasetIndex: dsIndex,
                            index: targetIdx,
                        });
                    }
                });
                if (synthesized.length > 0) {
                    elements = synthesized;
                }
            }

            const widgetJson = this._widgetData.get(container);
            const controls = widgetJson?.controls;
            const resultFormat = this.getKpiResultFormat(controls);
            const chartType = chart.config.type;
            const xMetricName = this.getMetricName(controls?.metrics?.[1]);
            const yMetricName = this.getMetricName(controls?.metrics?.[0]);

            const anomalyDates = widgetJson?.data?.anomaly_dates ?? [];
            const anomalySet = anomalyDates.length
                ? new Set(anomalyDates)
                : null;

            let html = "";
            const isMulti = elements.length > 1;
            if (isMulti && chartType !== "scatter") {
                const headerLabel = chart.data.labels?.[elements[0].index] || "";
                if (headerLabel) {
                    html +=
                        '<div style="font-weight:700;font-size:12px;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid ' +
                        (document.documentElement.classList.contains("dark") ? "#374151" : "#E5E7EB") +
                        ';">' +
                        headerLabel +
                        '</div>';
                }
            }

            elements.forEach((el) => {
                const ds = chart.data.datasets[el.datasetIndex];
                const raw = ds.data[el.index];
                let val;
                if (chartType === "scatter") {
                    const point = raw;
                    const valX = this.formatMetricValue(
                        point.x,
                        controls?.metrics?.[1],
                    );
                    const valY = this.formatMetricValue(
                        point.y,
                        controls?.metrics?.[0],
                    );
                    const baseLabel = point.label ? point.label + " — " : "";
                    val =
                        baseLabel +
                        "(" +
                        valX +
                        " " +
                        xMetricName +
                        ", " +
                        valY +
                        " " +
                        yMetricName +
                        ")";
                } else if (['pie', 'doughnut'].includes(chartType)) {
                    const label = chart.data.labels?.[el.index] || "";
                    let v = typeof raw === "object" ? (raw.y ?? 0) : raw;
                    let formattedVal = this.formatNumber(v);
                    if (ds.currency || resultFormat?.format === "currency") {
                        formattedVal = this.formatCurrency(v);
                    } else if (ds.percentage || resultFormat?.format === "percentage") {
                        formattedVal = v.toFixed(1) + "%";
                    }
                    const total = (ds.data || []).reduce((acc, curr) => acc + (typeof curr === 'number' ? curr : 0), 0);
                    const pct = total > 0 ? ((v / total) * 100).toFixed(1) + "%" : "0%";
                    val = (label ? `<span style="color:#9ca3af;margin-right:6px;">${label}:</span>` : "") +
                          `<span style="font-weight:600;">${formattedVal}</span> <span style="color:#6b7280;font-size:11px;">(${pct})</span>`;
                } else {
                    const label = chart.data.labels?.[el.index] || "";
                    let v = typeof raw === "object" ? (raw.y ?? 0) : raw;
                    if (!ds.percentage && resultFormat?.multiply) v = v * resultFormat.multiply;
                    if (ds.currency || resultFormat?.format === "currency") {
                        val = this.formatCurrency(v);
                    } else if (
                        ds.percentage ||
                        resultFormat?.format === "percentage"
                    ) {
                        val = v.toFixed(1) + "%";
                    } else {
                        val = this.formatNumber(v);
                    }
                    const dsLabel = ds.label || yMetricName;
                    if (isMulti) {
                        val = '<span style="color:#9ca3af;margin-right:6px;">' + dsLabel + ':</span>' + val;
                    } else {
                        val = (label ? label + " — " : "") + val + " " + dsLabel;
                    }
                }
                const isLine = ds.type === "line";
                const color = ds.borderColor || ds.backgroundColor || "#3B82F6";
                const colorStr = Array.isArray(color) ? color[el.index] : color;
                const iconSvg = this.getChannelIconSvg(ds.channel, ds.dependency, ds.label);
                html +=
                    '<div style="display:flex;align-items:center;gap:6px;padding:1px 0;">' +
                    '<span style="width:8px;height:8px;border-radius:50%;background:' +
                    colorStr +
                    ';flex-shrink:0;"></span>' +
                    (iconSvg ? '<span style="display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;opacity:0.75;margin-right:-2px;">' + iconSvg + '</span>' : "") +
                    (isLine && !isMulti
                        ? '<span style="font-weight:500;color:#9ca3af;font-size:11px;">Trend: </span>'
                        : "") +
                    '<span style="font-weight:600;">' +
                    val +
                    "</span>" +
                    "</div>";

                if (anomalySet) {
                    const dateLabel = chart.data.labels?.[el.index];
                    if (dateLabel && anomalySet.has(dateLabel)) {
                        html +=
                            '<div style="display:flex;align-items:center;gap:4px;padding:1px 0;margin-top:2px;">' +
                            '<span style="color:#EF4444;font-weight:600;font-size:11px;">⚠ Anomaly detected</span>' +
                            "</div>";
                    }
                }
            });
            const meta = chart.getDatasetMeta(elements[0].datasetIndex);
            const element = meta.data[elements[0].index];
            this._pinnedTooltips.set(container, {
                html,
                caretX: element.x,
                caretY: element.y,
            });
            this._renderPinnedTooltip(container);
        });
    },

    /**
     * Pop a widget's canvas/chart into a different container (fullscreen modal).
     */
    popOutWidget(containerEl, targetEl) {
        let json = this._widgetData.get(containerEl);
        if (!json && containerEl.dataset.widgetId) {
            const wid = String(containerEl.dataset.widgetId);
            for (const [, val] of this._widgetData.entries()) {
                if (val && String(val.id) === wid) {
                    json = val;
                    this._widgetData.set(containerEl, json);
                    break;
                }
            }
        }

        // Ensure the chart is rendered before moving its canvas
        this.flushRender(containerEl);

        if (!json) {
            json = this._widgetData.get(containerEl);
        }

        // Transfer widget data and pinned tooltip to the modal container
        if (json) {
            this._widgetData.set(targetEl, json);
        }
        if (this._pinnedTooltips.has(containerEl)) {
            this._pinnedTooltips.set(
                targetEl,
                this._pinnedTooltips.get(containerEl),
            );
            this._pinnedTooltips.delete(containerEl);
        }

        const canvas = containerEl.querySelector("canvas");
        if (canvas) {
            targetEl.style.display = "flex";
            targetEl.style.flexDirection = "column";
            targetEl.style.width = "100%";
            targetEl.style.height = "100%";
            targetEl.style.overflow = "hidden";
            targetEl.innerHTML = "";

            // Move entire child tree (canvasWrap + r2 badge + tooltip + toggles) into modal
            while (containerEl.children.length > 0) {
                targetEl.appendChild(containerEl.children[0]);
            }
            let chart = this._chartInstances.get(containerEl);
            if (!chart && window.Chart?.getChart) {
                chart = window.Chart.getChart(canvas);
            }
            if (chart) {
                this._chartInstances.set(targetEl, chart);
                this._chartInstances.delete(containerEl);
                if (this._popOutObserver) this._popOutObserver.disconnect();
                this._popOutObserver = new ResizeObserver(() => {
                    chart.resize();
                });
                this._popOutObserver.observe(targetEl);
                chart.resize();
                if (this._pinnedTooltips.has(targetEl)) {
                    this._renderPinnedTooltip(targetEl);
                }
            }
            return;
        }
        // HTML widget — clone content into target
        targetEl.innerHTML = containerEl.innerHTML;

        // Re-attach scroll sync for table widgets (blocked first column)
        const scrollWrap = targetEl.querySelector(".table-scroll-wrap");
        const fixedWrap = targetEl.querySelector(".fixed-col-wrap");
        if (scrollWrap && fixedWrap) {
            scrollWrap.addEventListener("scroll", () => {
                fixedWrap.scrollTop = scrollWrap.scrollTop;
            });
        }
    },

    /**
     * Pop the widget back to its original container.
     * If the container was already re-rendered while popped out, discard the modal's canvas.
     */
    popInWidget(containerEl, targetEl) {
        if (this._popOutObserver) {
            this._popOutObserver.disconnect();
            this._popOutObserver = null;
        }
        const canvas = targetEl.querySelector("canvas");
        if (canvas) {
            const existingCanvas = containerEl.querySelector("canvas");
            if (existingCanvas) {
                // Grid was reloaded while popped out — discard modal's stale chart
                const chart = this._chartInstances.get(targetEl);
                if (chart) {
                    chart.destroy();
                    this._chartInstances.delete(targetEl);
                }
                targetEl.innerHTML = "";
                return;
            }
            containerEl.style.display = "flex";
            containerEl.style.flexDirection = "column";
            containerEl.style.width = "100%";
            containerEl.style.height = "100%";
            containerEl.style.overflow = "hidden";
            containerEl.innerHTML = "";

            // Move entire child tree back into widget container
            while (targetEl.children.length > 0) {
                containerEl.appendChild(targetEl.children[0]);
            }
            // Transfer widget data and pinned tooltip back to original container
            if (this._widgetData.has(targetEl)) {
                this._widgetData.set(
                    containerEl,
                    this._widgetData.get(targetEl),
                );
                this._widgetData.delete(targetEl);
            }
            if (this._pinnedTooltips.has(targetEl)) {
                this._pinnedTooltips.set(
                    containerEl,
                    this._pinnedTooltips.get(targetEl),
                );
                this._pinnedTooltips.delete(targetEl);
            }
            const chart = this._chartInstances.get(targetEl);
            if (chart) {
                this._chartInstances.set(containerEl, chart);
                this._chartInstances.delete(targetEl);
                chart.resize();
                if (this._pinnedTooltips.has(containerEl)) {
                    this._renderPinnedTooltip(containerEl);
                }
            }
            return;
        }
        // HTML widget — content was cloned, not moved. Just clear the modal.
        targetEl.innerHTML = "";
    },

    _renderWidget(widget_type, containerEl, data, controls) {
        containerEl.style.padding = "";
        containerEl.style.overflow = "";
        switch (widget_type) {
            case "tile":
                this.renderTile(containerEl, data, controls);
                break;
            case "line_chart":
                this.renderLineChart(containerEl, data, controls);
                break;
            case "bar_chart":
                this.renderBarChart(containerEl, data, controls);
                break;
            case "table":
                this.renderTable(containerEl, data, controls);
                break;
            case "gauge":
                this.renderGauge(containerEl, data, controls);
                break;
            case "sparkline":
                this.renderSparkline(containerEl, data, controls);
                break;
            case "anomaly_list":
                this.renderAnomalyList(containerEl, data);
                break;
            case "anomaly_chart":
                this.renderAnomalyChart(containerEl, data, controls);
                break;
            case "scatter_plot":
                this.renderScatterPlot(containerEl, data, controls);
                break;
            case "combo_chart":
                this.renderComboChart(containerEl, data, controls);
                break;
            case "pie_chart":
                this.renderPieChart(containerEl, data, controls);
                break;
            case "choropleth_map":
                this.renderChoroplethMap(containerEl, data, controls);
                break;
            case "pinnable_map":
                this.renderPinnableMap(containerEl, data, controls);
                break;
            default:
                containerEl.innerHTML =
                    '<div class="text-sm text-gray-400 p-4 text-center">Unknown widget type: ' +
                    widget_type +
                    "</div>";
        }
    },

    // ─── Formatters ───

    formatNumber(n) {
        if (typeof n === "string" && n.includes(":")) return n;
        if (n == null || isNaN(n)) return "—";
        return Number(n).toLocaleString("en-US", { maximumFractionDigits: 1 });
    },

    _formatTableNumber(n) {
        if (typeof n === "string" && n.includes(":")) return n;
        if (n == null || isNaN(n)) return "—";
        const num = Number(n);
        if (Number.isInteger(num))
            return num.toLocaleString("en-US", { maximumFractionDigits: 0 });
        return num.toLocaleString("en-US", {
            minimumFractionDigits: 4,
            maximumFractionDigits: 4,
        });
    },

    formatCurrency(n) {
        if (n == null || isNaN(n)) return "—";
        return (
            "$" +
            Number(n).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    },

    // ─── Metric Display Helpers ───

    formatMetricValue(value, metricKey) {
        const fmt = this.METRIC_FORMATS[metricKey];
        if (!fmt) return this.formatNumber(value);
        let v = value;
        if (fmt.multiply) v = v * fmt.multiply;
        if (fmt.format === "currency")
            return (fmt.prefix || "$") + this.formatNumber(v);
        if (fmt.format === "percentage") return v.toFixed(1) + "%";
        return this.formatNumber(v);
    },

    getMetricName(metricKey) {
        const fmt = this.METRIC_FORMATS[metricKey];
        return fmt?.label || metricKey || "Value";
    },

    // ─── External HTML Tooltip ───

    _setupTooltip(chart, containerEl) {
        let tooltipEl = containerEl.querySelector(".chart-tooltip");
        if (!tooltipEl) {
            tooltipEl = document.createElement("div");
            tooltipEl.className = "chart-tooltip";
            tooltipEl.style.position = "absolute";
            tooltipEl.style.pointerEvents = "none";
            tooltipEl.style.opacity = "0";
            tooltipEl.style.transition = "opacity 0.15s ease";
            tooltipEl.style.zIndex = "100";
            tooltipEl.style.whiteSpace = "nowrap";
            containerEl.appendChild(tooltipEl);
        }
        return tooltipEl;
    },

    _positionTooltip(tooltipEl, containerEl, caretX, caretY) {
        const containerRect = containerEl.getBoundingClientRect();
        const canvas = containerEl.querySelector("canvas");
        if (!canvas) return;
        const chartRect = canvas.getBoundingClientRect();
        const offsetLeft = chartRect.left - containerRect.left;
        const offsetTop = chartRect.top - containerRect.top;

        const cx = caretX + offsetLeft;
        const cy = caretY + offsetTop;
        const tw = tooltipEl.offsetWidth;
        const th = tooltipEl.offsetHeight;
        const cw = containerRect.width;
        const ch = containerRect.height;

        // Dynamic max-width: fit inside container with 16px margin
        tooltipEl.style.maxWidth = Math.max(160, cw - 32) + "px";

        // Re-check width after maxWidth constraint
        const finalTw = tooltipEl.offsetWidth;

        // Smart quadrant-based positioning
        const gap = 8;
        const preferRight = cx < cw / 2;
        const preferBelow = cy < ch / 2;

        let left, top;

        // Horizontal: prefer opposite side from caret
        if (preferRight) {
            left = cx + gap;
            if (left + finalTw > cw - gap) left = cx - finalTw - gap;
        } else {
            left = cx - finalTw - gap;
            if (left < gap) left = cx + gap;
        }
        // Clamp horizontal
        left = Math.max(gap, Math.min(left, cw - finalTw - gap));

        // Vertical: prefer opposite side from caret
        if (preferBelow) {
            top = cy + gap;
            if (top + th > ch - gap) top = cy - th - gap;
        } else {
            top = cy - th - gap;
            if (top < gap) top = cy + gap;
        }
        // Clamp vertical
        top = Math.max(gap, Math.min(top, ch - th - gap));

        tooltipEl.style.left = Math.round(left) + "px";
        tooltipEl.style.top = Math.round(top) + "px";
    },

    _externalTooltipHandler(context) {
        try {
            const { chart, tooltip } = context;
            if (!chart || !chart.canvas) return;
            const container = chart.canvas.parentNode;
            if (!container) return;
            const tooltipEl = this._setupTooltip(chart, container);

            // If pinned, always show pinned data regardless of hover
            const pinned = this._pinnedTooltips.get(container);
            if (pinned) {
                this._renderPinnedTooltip(container);
                return;
            }

            if (!tooltip || tooltip.opacity < 0.01) {
                tooltipEl.style.opacity = "0";
                tooltipEl.innerHTML = "";
                tooltipEl.style.pointerEvents = "none";
                return;
            }

            const isDark = document.documentElement.classList.contains("dark");
            const bg = isDark ? "#1F2937" : "#FFF";
            const textColor = isDark ? "#F3F4F6" : "#111827";
            const borderColor = isDark ? "#374151" : "#E5E7EB";

            tooltipEl.style.background = bg;
            tooltipEl.style.color = textColor;
            tooltipEl.style.border = "1px solid " + borderColor;
            tooltipEl.style.borderRadius = "8px";
            tooltipEl.style.padding = "8px 12px";
            tooltipEl.style.fontSize = "12px";
            tooltipEl.style.lineHeight = "1.5";
            tooltipEl.style.boxShadow = isDark
                ? "0 4px 16px rgba(0,0,0,0.4)"
                : "0 4px 16px rgba(0,0,0,0.12)";
            tooltipEl.style.fontFamily = "inherit";

            const widgetJson = this._widgetData.get(container);
            const ctrl = widgetJson?.controls;
            const rFmt = this.getKpiResultFormat(ctrl);
            const chartType = chart.config.type;
            const xMN = this.getMetricName(ctrl?.metrics?.[1]);
            const yMN = this.getMetricName(ctrl?.metrics?.[0]);

            const anomalyDates = widgetJson?.data?.anomaly_dates ?? [];
            const anomalySet = anomalyDates.length
                ? new Set(anomalyDates)
                : null;

            const isCrosshair = this._isCrosshairActive;
            let dataPoints = tooltip.dataPoints || [];
            if (
                isCrosshair &&
                (chartType === "line" || chartType === "bar") &&
                dataPoints.length > 0
            ) {
                const targetIndex = dataPoints[0].dataIndex;
                const synthesized = [];
                chart.data.datasets.forEach((ds, dsIndex) => {
                    const meta = chart.getDatasetMeta(dsIndex);
                    if (meta.hidden || ds.hidden) return;
                    const raw = ds.data[targetIndex];
                    if (raw === undefined || raw === null) return;
                    synthesized.push({
                        chart,
                        dataset: ds,
                        datasetIndex: dsIndex,
                        dataIndex: targetIndex,
                        raw,
                        formattedValue:
                            typeof raw === "object" ? raw.y ?? 0 : raw,
                    });
                });
                if (synthesized.length > 0) {
                    dataPoints = synthesized;
                }
            }

            let html = "";
            const isMulti = (dataPoints.length || 0) > 1;
            if (isMulti && chartType !== "scatter" && dataPoints.length > 0) {
                const headerLabel =
                    chart.data.labels?.[dataPoints[0].dataIndex] || "";
                if (headerLabel) {
                    html +=
                        '<div style="font-weight:700;font-size:12px;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid ' +
                        borderColor +
                        ';">' +
                        headerLabel +
                        '</div>';
                }
            }

            if (dataPoints.length) {
                dataPoints.forEach((dp) => {
                    if (!dp) return;
                    let val;
                    if (chartType === "scatter") {
                        const point = dp.raw;
                        const vX = this.formatMetricValue(
                            point.x,
                            ctrl?.metrics?.[1],
                        );
                        const vY = this.formatMetricValue(
                            point.y,
                            ctrl?.metrics?.[0],
                        );
                        const baseLabel = point.label
                            ? point.label + " — "
                            : "";
                        val =
                            baseLabel +
                            "(" +
                            vX +
                            " " +
                            xMN +
                            ", " +
                            vY +
                            " " +
                            yMN +
                            ")";
                    } else {
                        const label = chart.data.labels?.[dp.dataIndex] || "";
                        let v =
                            typeof dp.raw === "object"
                                ? (dp.raw.y ?? 0)
                                : dp.raw;
                        const dsMetric = String(dp.dataset.metric || dp.dataset.metric_key || dp.dataset.key || "").toLowerCase();
                        const isPos = dsMetric.includes("position") || (!isMulti && ctrl?.metrics?.[0] === "position");

                        if (!isPos && !dp.dataset.percentage && rFmt?.multiply) v = v * rFmt.multiply;
                        if (!isPos && (dp.dataset.currency || rFmt?.format === "currency")) {
                            val = this.formatCurrency(v);
                        } else if (!isPos && (dp.dataset.percentage || rFmt?.format === "percentage")) {
                            val = v.toFixed(1) + "%";
                        } else {
                            val = this.formatNumber(v);
                        }
                        const dsLabel = dp.dataset.label || yMN;
                        if (isMulti) {
                            val = '<span style="color:#9ca3af;margin-right:6px;">' + dsLabel + ':</span>' + val;
                        } else {
                            val = (label ? label + " — " : "") + val + " " + dsLabel;
                        }
                    }
                    const isLine = dp.dataset.type === "line";
                    const color =
                        dp.dataset.borderColor ||
                        dp.dataset.backgroundColor ||
                        "#3B82F6";
                    const iconSvg = this.getChannelIconSvg(
                        dp.dataset.channel,
                        dp.dataset.dependency,
                        dp.dataset.label || yMN
                    );
                    html +=
                        '<div style="display:flex;align-items:center;gap:6px;padding:1px 0;">' +
                        '<span style="width:8px;height:8px;border-radius:50%;background:' +
                        color +
                        ';flex-shrink:0;"></span>' +
                        (iconSvg
                            ? '<span style="display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;opacity:0.75;margin-right:-2px;">' +
                              iconSvg +
                              '</span>'
                            : "") +
                        (isLine && !isMulti
                            ? '<span style="font-weight:500;color:#9ca3af;font-size:11px;">Trend: </span>'
                            : "") +
                        '<span style="font-weight:600;">' +
                        val +
                        "</span>" +
                        "</div>";

                    if (anomalySet) {
                        const dateLabel = chart.data.labels?.[dp.dataIndex];
                        if (dateLabel && anomalySet.has(dateLabel)) {
                            html +=
                                '<div style="display:flex;align-items:center;gap:4px;padding:1px 0;margin-top:2px;">' +
                                '<span style="color:#EF4444;font-weight:600;font-size:11px;">⚠ Anomaly detected</span>' +
                                "</div>";
                        }
                    }
                });
            }

            tooltipEl.innerHTML = html;
            tooltipEl.style.pointerEvents = "none";

            this._positionTooltip(
                tooltipEl,
                container,
                tooltip.caretX,
                tooltip.caretY,
            );
            tooltipEl.style.opacity = "1";
        } catch (e) {
            console.warn("[Tooltip]", e);
        }
    },

    _renderPinnedTooltip(container) {
        const pinned = this._pinnedTooltips.get(container);
        if (!pinned) return;
        const tooltipEl = container.querySelector(".chart-tooltip");
        if (!tooltipEl) return;

        const isDark = document.documentElement.classList.contains("dark");
        const bg = isDark ? "#1F2937" : "#FFF";
        const textColor = isDark ? "#F3F4F6" : "#111827";
        const borderColor = isDark ? "#374151" : "#E5E7EB";

        tooltipEl.style.background = bg;
        tooltipEl.style.color = textColor;
        tooltipEl.style.border = "1px solid " + borderColor;
        tooltipEl.style.borderRadius = "8px";
        tooltipEl.style.padding = "8px 12px";
        tooltipEl.style.fontSize = "12px";
        tooltipEl.style.lineHeight = "1.5";
        tooltipEl.style.boxShadow = isDark
            ? "0 4px 16px rgba(0,0,0,0.4)"
            : "0 4px 16px rgba(0,0,0,0.12)";
        tooltipEl.style.fontFamily = "inherit";
        tooltipEl.style.pointerEvents = "auto";
        tooltipEl.innerHTML = pinned.html;

        this._positionTooltip(
            tooltipEl,
            container,
            pinned.caretX,
            pinned.caretY,
        );
        tooltipEl.style.opacity = "1";
    },

    // ─── Choropleth Map Widget ───

    renderChoroplethMap(containerEl, data, controls) {
        const uniqueId = 'map-widget-' + Math.random().toString(36).substring(2, 9);
        const seriesMetrics = Array.isArray(data?.series_metrics) && data.series_metrics.length > 0 ? data.series_metrics : null;
        let activeIdx = 0;

        const getActiveData = (idx) => {
            let activeItem = null;
            if (seriesMetrics && seriesMetrics[idx]) {
                activeItem = { ...seriesMetrics[idx] };
            } else {
                activeItem = {
                    metric: data?.metric || controls?.metrics?.[0] || 'clicks',
                    metric_label: data?.metric_label || controls?.metrics?.[0] || 'Value',
                    color: data?.color || null,
                    map_data: data?.map_data || {},
                    leaderboard: data?.leaderboard || []
                };
            }

            const activeMKey = activeItem.metric ? String(activeItem.metric).toLowerCase() : '';
            const userMetricColor = controls?.series_metric_colors?.[0]?.[activeMKey]
                ?? controls?.series_metric_colors?.['0']?.[activeMKey]
                ?? controls?.raw_series?.[0]?.metric_colors?.[activeMKey]
                ?? controls?.series?.[0]?.color
                ?? controls?.color
                ?? '#00a7f9';

            if (!activeItem.color || activeItem.color.startsWith('rgb')) {
                activeItem.color = userMetricColor;
            }

            return activeItem;
        };

        const initialActive = getActiveData(activeIdx);
        const rawMapData = initialActive.map_data;
        const mapData = (rawMapData && typeof rawMapData === 'object' && !Array.isArray(rawMapData)) ? rawMapData : {};
        const regionsCount = Object.keys(mapData).length;

        containerEl.style.display = "flex";
        containerEl.style.flexDirection = "column";
        containerEl.style.width = "100%";
        containerEl.style.height = "100%";
        containerEl.style.position = "relative";

        // Safeguard / Empty State when no country breakdown is available
        if (regionsCount === 0) {
            containerEl.innerHTML = `
                <div class="w-full h-full p-4 flex flex-col items-center justify-center text-center bg-gray-50/50 dark:bg-gray-900/20 rounded-lg border border-dashed border-gray-200 dark:border-gray-800">
                    <div class="w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3 ring-8 ring-amber-50/50 dark:ring-amber-950/20">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none" stroke-width="1.75">
                            <circle cx="12" cy="12" r="9"/>
                            <ellipse cx="12" cy="12" rx="4.5" ry="9" stroke-dasharray="1 1"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 mb-1">Country Breakdown Required</span>
                    <p class="text-2xs text-gray-400 dark:text-gray-500 max-w-[280px] leading-relaxed">
                        This widget requires data broken down by country to colorize geographic regions. Edit widget controls to select the <strong>Country</strong> dimension and <strong>Lifetime</strong> granularity.
                    </p>
                </div>
            `;
            return;
        }

        // Multi-metric Pill Switcher Header
        let pillHeaderHtml = '';
        if (seriesMetrics && seriesMetrics.length > 1) {
            pillHeaderHtml = `
                <div class="flex items-center gap-1 overflow-x-auto no-scrollbar py-0.5 max-w-full">
                    ${seriesMetrics.map((sm, i) => `
                        <button type="button" data-metric-idx="${i}" class="dash-map-pill px-2 py-0.5 rounded-md text-2xs font-semibold transition-all ${i === 0 ? 'bg-primary-500 text-white shadow-2xs' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-800'}">
                            ${this.escapeHtml(sm.metric_label || sm.metric)}
                        </button>
                    `).join('')}
                </div>
            `;
        } else {
            pillHeaderHtml = `
                <span class="font-semibold uppercase tracking-wider text-2xs text-gray-600 dark:text-gray-400" id="${uniqueId}-metric-title">${this.escapeHtml(initialActive.metric_label || 'Value')} Distribution</span>
            `;
        }

        // View Switcher (Map vs List) matching Data Explorer design
        const viewSwitcherHtml = `
            <div class="flex items-center p-0.5 rounded-md bg-gray-100 dark:bg-gray-800/80 border border-gray-200/60 dark:border-gray-700/60 flex-shrink-0" id="${uniqueId}-view-toggle">
                <button type="button" data-view="map" class="dash-map-view-btn is-active" title="Map View">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.549l.062.028.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/>
                    </svg>
                    <span>Map</span>
                </button>
                <button type="button" data-view="list" class="dash-map-view-btn" title="List View">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z" clip-rule="evenodd"/>
                    </svg>
                    <span>List</span>
                </button>
            </div>
        `;

        containerEl.innerHTML = `
            <div class="w-full h-full p-2 flex flex-col">
                <div class="flex items-center justify-between pb-1.5 border-b border-gray-100 dark:border-white/5 flex-shrink-0 gap-2">
                    <div class="flex-1 min-w-0">${pillHeaderHtml}</div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span id="${uniqueId}-regions-count" class="text-3xs text-primary-500 font-mono hidden sm:inline-block">${regionsCount} Regions</span>
                        ${viewSwitcherHtml}
                    </div>
                </div>
                <div id="${uniqueId}-content" class="w-full flex-1 min-h-[180px] relative mt-1 overflow-hidden">
                    <div id="${uniqueId}" class="w-full h-full relative"></div>
                    <div id="${uniqueId}-list-container" class="dash-widget-list-view" style="display: none;"></div>
                </div>
            </div>
        `;

        const mapEl = containerEl.querySelector('#' + uniqueId);
        const listContainerEl = containerEl.querySelector('#' + uniqueId + '-list-container');
        if (!mapEl) return;

        // Color computation helper: converts hex to RGB and builds light/dark tints
        const hexToRgb = (hex) => {
            if (!hex || typeof hex !== 'string') return [2, 132, 199];
            let c = hex.replace('#', '').trim();
            if (c.length === 3) c = c[0] + c[0] + c[1] + c[1] + c[2] + c[2];
            if (c.length !== 6) return [2, 132, 199];
            const num = parseInt(c, 16);
            return [(num >> 16) & 255, (num >> 8) & 255, num & 255];
        };

        // Helper to compute graded fill colors for each region according to the value range
        const computeRegionColorGrading = (mapDataObj, baseHex, isDark, isLowerBetter) => {
            const numEntries = Object.entries(mapDataObj || {}).map(([code, val]) => [code, Number(val)]).filter(([_, val]) => !isNaN(val) && val > 0);
            if (!numEntries.length) return {};

            const vals = numEntries.map(([_, val]) => val);
            const minVal = Math.min(...vals);
            const maxVal = Math.max(...vals);
            const diff = maxVal - minVal;

            const [r, g, b] = hexToRgb(baseHex);
            // Lightest tint (low intensity) -> Full vibrant baseHex (high intensity)
            let startRgb, endRgb;
            if (isDark) {
                // In dark mode: start with dark muted tint of base color, end with vibrant base
                startRgb = [
                    Math.round(20 + r * 0.18),
                    Math.round(26 + g * 0.18),
                    Math.round(44 + b * 0.22)
                ];
                endRgb = [r, g, b];
            } else {
                // In light mode: start with very soft pastel tint, end with vibrant base
                startRgb = [
                    Math.round(255 - (255 - r) * 0.18),
                    Math.round(255 - (255 - g) * 0.18),
                    Math.round(255 - (255 - b) * 0.18)
                ];
                endRgb = [r, g, b];
            }

            const gradedColors = {};
            for (const [code, val] of numEntries) {
                let ratio = 0.8;
                if (diff > 0) {
                    if (isLowerBetter) {
                        // For AVG position: lower value is better/favored (e.g., position 1 gets vibrant base, position 70 gets light tint)
                        const linearRatio = (maxVal - val) / diff;
                        // Use pow/linear for position so top rankings (1-5) stand out richly
                        ratio = Math.max(0, Math.min(1, Math.pow(linearRatio, 0.85)));
                    } else {
                        // Standard volume metrics (clicks, impressions): logarithmic scale so outliers don't crush smaller countries
                        ratio = Math.log(val - minVal + 1) / Math.log(diff + 1);
                    }
                }
                ratio = Math.max(0, Math.min(1, ratio));

                const curR = Math.round(startRgb[0] + (endRgb[0] - startRgb[0]) * ratio);
                const curG = Math.round(startRgb[1] + (endRgb[1] - startRgb[1]) * ratio);
                const curB = Math.round(startRgb[2] + (endRgb[2] - startRgb[2]) * ratio);
                gradedColors[code.toUpperCase()] = `#${((1 << 24) + (curR << 16) + (curG << 8) + curB).toString(16).slice(1)}`;
            }
            return gradedColors;
        };

        const applyRegionColors = (inst, gradedColors, defaultFill, defaultStroke) => {
            if (!inst || !inst.regions) return;
            for (const [code, regionObj] of Object.entries(inst.regions)) {
                if (regionObj && regionObj.element) {
                    const color = gradedColors[code.toUpperCase()] || defaultFill;
                    const shape = regionObj.element.shape;
                    if (shape && shape.style && shape.style.initial) {
                        shape.style.initial.fill = color;
                        shape.style.initial.stroke = defaultStroke;
                    }
                    if (typeof regionObj.element.setStyle === 'function') {
                        regionObj.element.setStyle('fill', color);
                        regionObj.element.setStyle('stroke', defaultStroke);
                    }
                }
            }
        };

        // Render List View content
        const renderListView = (metricItem, searchFilter = '') => {
            if (!listContainerEl) return;
            const items = metricItem.leaderboard || [];
            const query = (searchFilter || '').trim().toLowerCase();
            const filteredItems = query
                ? items.filter(it => (it.name && it.name.toLowerCase().includes(query)) || (it.code && it.code.toLowerCase().includes(query)))
                : items;

            const isRatio = String(metricItem.metric || '').toLowerCase() === 'ctr' || String(metricItem.metric || '').includes('rate');
            const isPos = isPosMetric(metricItem.metric);

            let listRowsHtml = '';
            if (!filteredItems.length) {
                listRowsHtml = `<div class="p-6 text-center text-xs text-gray-400 dark:text-gray-500">No countries match "${this.escapeHtml(query)}"</div>`;
            } else {
                listRowsHtml = filteredItems.map((row, idx) => {
                    const numVal = Number(row.value) || 0;
                    let displayVal = numVal.toLocaleString('en-US');
                    if (isRatio) {
                        displayVal = numVal.toFixed(2) + '%';
                    } else if (isPos) {
                        displayVal = numVal.toFixed(1);
                    }
                    return `
                        <div class="dash-leaderboard-item" data-country-code="${this.escapeHtml(row.code || '')}">
                            <div class="flex items-center gap-2 truncate pr-2 min-w-0">
                                <span class="dash-leaderboard-rank text-xs">${idx + 1}.</span>
                                <span class="dash-leaderboard-name font-medium truncate">${this.escapeHtml(row.name || row.code || 'Unknown')}</span>
                                ${row.code && row.code !== 'UNK' ? `<span class="text-3xs uppercase tracking-wider px-1 py-0.2 rounded bg-gray-200/60 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400 font-mono">${this.escapeHtml(row.code)}</span>` : ''}
                            </div>
                            <div class="flex items-center shrink-0">
                                <span class="dash-leaderboard-metric font-semibold font-mono" style="color: ${metricItem.color || 'inherit'}">${displayVal}</span>
                                <span class="dash-leaderboard-unit text-3xs text-gray-400 ml-1.5 uppercase">${this.escapeHtml(metricItem.metric_label || metricItem.metric)}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            listContainerEl.innerHTML = `
                <div class="flex items-center justify-between gap-2 p-1 border-b border-gray-100 dark:border-white/5 mb-1 flex-shrink-0">
                    <div class="relative flex-1 min-w-0">
                        <input type="text" value="${this.escapeHtml(searchFilter)}" placeholder="Filter countries..." class="dash-widget-list-search w-full px-2 py-1 pl-6 text-2xs rounded-md bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 absolute left-1.5 top-1.5 text-gray-400">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-3xs text-gray-400 font-mono flex-shrink-0">${filteredItems.length} Countries</span>
                </div>
                <div class="dash-widget-list-scroll">
                    ${listRowsHtml}
                </div>
            `;

            // Wire up search input
            const searchInput = listContainerEl.querySelector('.dash-widget-list-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    renderListView(currentMetricData, e.target.value);
                    const updatedInput = listContainerEl.querySelector('.dash-widget-list-search');
                    if (updatedInput) {
                        updatedInput.focus();
                        updatedInput.setSelectionRange(updatedInput.value.length, updatedInput.value.length);
                    }
                });
            }
        };

        const isPosMetric = (m) => {
            const s = String(m || '').toLowerCase();
            return s.includes('position') || s.includes('rank');
        };

        let currentMetricData = initialActive;
        let currentMapData = (initialActive.map_data && typeof initialActive.map_data === 'object' && !Array.isArray(initialActive.map_data)) ? initialActive.map_data : {};
        let currentBaseColor = currentMetricData.color || controls?.series?.[0]?.color || controls?.color || '#00a7f9';
        let isLowerBetter = isPosMetric(currentMetricData.metric);
        let activeView = 'map'; // 'map' or 'list' (used when in portrait/compact mode)
        let isLandscapeMode = false;

        const mainContentEl = containerEl.querySelector('#' + uniqueId + '-content');
        const viewToggleContainer = containerEl.querySelector('#' + uniqueId + '-view-toggle');

        // Apply Layout Mode (Two-column in Landscape, Flip/Toggle in Portrait/Square)
        const applyAdaptiveLayout = (width, height) => {
            if (!mainContentEl || !mapEl || !listContainerEl) return;
            const isWide = width >= 640 && (width / Math.max(1, height)) >= 1.35;
            isLandscapeMode = isWide;

            if (isWide) {
                // Landscape: Side-by-side 2 columns
                if (viewToggleContainer) viewToggleContainer.style.display = 'none';
                mainContentEl.className = 'w-full flex-1 min-h-[180px] relative mt-1 overflow-hidden flex flex-row gap-3 items-stretch';
                mapEl.style.display = 'block';
                mapEl.className = 'flex-1 min-w-0 h-full relative border border-gray-100 dark:border-white/5 rounded-lg overflow-hidden';
                listContainerEl.style.display = 'flex';
                listContainerEl.className = 'w-72 xl:w-80 flex-shrink-0 h-full bg-gray-50/50 dark:bg-gray-900/30 rounded-lg border border-gray-100 dark:border-white/5 p-1 dash-widget-list-view';
                renderListView(currentMetricData, '');
                if (window._currentMapInst) {
                    window._currentMapInst.updateSize();
                }
            } else {
                // Portrait or Square: Toggle/Flip between Map and List
                if (viewToggleContainer) viewToggleContainer.style.display = 'flex';
                mainContentEl.className = 'w-full flex-1 min-h-[180px] relative mt-1 overflow-hidden';
                mapEl.className = 'w-full h-full relative';
                listContainerEl.className = 'dash-widget-list-view';

                if (activeView === 'list') {
                    mapEl.style.display = 'none';
                    listContainerEl.style.display = 'flex';
                    renderListView(currentMetricData, '');
                } else {
                    listContainerEl.style.display = 'none';
                    mapEl.style.display = 'block';
                    if (window._currentMapInst) {
                        window._currentMapInst.updateSize();
                    }
                }
            }
        };

        // Render initial List view in background
        renderListView(currentMetricData, '');

        // Wire View Switcher (Map vs List) for toggle mode
        if (viewToggleContainer) {
            const toggleBtns = viewToggleContainer.querySelectorAll('.dash-map-view-btn');
            toggleBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const v = btn.getAttribute('data-view');
                    if (!v || v === activeView) return;
                    activeView = v;

                    toggleBtns.forEach(b => {
                        if (b.getAttribute('data-view') === activeView) {
                            b.classList.add('is-active');
                        } else {
                            b.classList.remove('is-active');
                        }
                    });

                    if (!isLandscapeMode) {
                        if (activeView === 'list') {
                            mapEl.style.display = 'none';
                            listContainerEl.style.display = 'flex';
                            renderListView(currentMetricData, '');
                        } else {
                            listContainerEl.style.display = 'none';
                            mapEl.style.display = 'block';
                            if (window._currentMapInst) {
                                window._currentMapInst.updateSize();
                            }
                        }
                    }
                });
            });
        }

        // Setup ResizeObserver to automatically switch between 2-column landscape and toggle mode
        if (window.ResizeObserver) {
            const ro = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    const cr = entry.contentRect;
                    if (cr && cr.width > 0 && cr.height > 0) {
                        applyAdaptiveLayout(cr.width, cr.height);
                    }
                }
            });
            ro.observe(containerEl);
        } else {
            applyAdaptiveLayout(containerEl.clientWidth || 500, containerEl.clientHeight || 300);
        }

        if (window.jsVectorMap) {
            const isDark = document.documentElement.classList.contains("dark");
            const defaultFill = isDark ? '#243042' : '#E2E8F0';
            const defaultStroke = isDark ? '#334155' : '#cbd5e1';

            let gradedColors = computeRegionColorGrading(currentMapData, currentBaseColor, isDark, isLowerBetter);

            const mapInst = new window.jsVectorMap({
                selector: '#' + uniqueId,
                map: 'world',
                backgroundColor: 'transparent',
                draggable: true,
                zoomButtons: true,
                zoomOnScroll: false,
                regionStyle: {
                    initial: {
                        fill: defaultFill,
                        stroke: defaultStroke,
                        strokeWidth: 0.5,
                        fillOpacity: 1
                    },
                    hover: {
                        fill: currentBaseColor,
                        fillOpacity: 0.9,
                        cursor: 'pointer'
                    }
                },
                onRegionTooltipShow(event, tooltip, code) {
                    const val = currentMapData[code.toUpperCase()];
                    const formatted = val !== undefined ? Number(val).toLocaleString('en-US') : '0';
                    const countryName = tooltip.text();
                    tooltip.text(
                        `<div class="dash-map-tooltip">
                            <span class="dash-map-tooltip-name">${countryName}</span>
                            <span class="dash-map-tooltip-divider">•</span>
                            <span class="dash-map-tooltip-val">${formatted}<span class="dash-map-tooltip-metric" style="margin-left:4px; font-weight:normal; opacity:0.8;">${currentMetricData.metric_label || 'Value'}</span></span>
                        </div>`,
                        true
                    );
                }
            });

            // Immediately apply graded region fills so countries have their graded metric color statically
            applyRegionColors(mapInst, gradedColors, defaultFill, defaultStroke);

            this._chartInstances.set(containerEl, mapInst);
            window._currentMapInst = mapInst;

            // Handle Multi-Metric Header Pill Switches
            if (seriesMetrics && seriesMetrics.length > 1) {
                const pills = containerEl.querySelectorAll('.dash-map-pill');
                pills.forEach(pill => {
                    pill.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const idx = parseInt(pill.getAttribute('data-metric-idx'), 10);
                        if (isNaN(idx) || idx === activeIdx) return;
                        activeIdx = idx;

                        pills.forEach((p, pi) => {
                            if (pi === activeIdx) {
                                p.className = 'dash-map-pill px-2 py-0.5 rounded-md text-2xs font-semibold transition-all bg-primary-500 text-white shadow-2xs';
                            } else {
                                p.className = 'dash-map-pill px-2 py-0.5 rounded-md text-2xs font-semibold transition-all text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-800';
                            }
                        });

                        currentMetricData = getActiveData(activeIdx);
                        const rawCurrentMap = currentMetricData.map_data;
                        currentMapData = (rawCurrentMap && typeof rawCurrentMap === 'object' && !Array.isArray(rawCurrentMap)) ? rawCurrentMap : {};
                        currentBaseColor = currentMetricData.color || controls?.series?.[activeIdx]?.color || controls?.color || '#00a7f9';
                        isLowerBetter = isPosMetric(currentMetricData.metric);

                        const countEl = containerEl.querySelector('#' + uniqueId + '-regions-count');
                        if (countEl) countEl.textContent = `${Object.keys(currentMapData).length} Regions`;

                        // Refresh list view with current metric data
                        renderListView(currentMetricData, '');

                        // Update hover fill to matching base color
                        if (mapInst.params && mapInst.params.regionStyle && mapInst.params.regionStyle.hover) {
                            mapInst.params.regionStyle.hover.fill = currentBaseColor;
                        }

                        // Recompute graded colors for the newly selected metric and apply
                        const updatedGradedColors = computeRegionColorGrading(currentMapData, currentBaseColor, isDark, isLowerBetter);
                        applyRegionColors(mapInst, updatedGradedColors, defaultFill, defaultStroke);
                    });
                });
            }
        } else {
            mapEl.innerHTML = `<div class="flex items-center justify-center h-full text-xs text-gray-400">Map library initializing...</div>`;
        }
    },

    // ─── Pinnable Map Widget ───

    renderPinnableMap(containerEl, data, controls) {
        const pins = data?.pins || [];
        const uniqueId = 'pin-map-widget-' + Math.random().toString(36).substring(2, 9);

        containerEl.style.display = "flex";
        containerEl.style.flexDirection = "column";
        containerEl.style.width = "100%";
        containerEl.style.height = "100%";
        containerEl.style.position = "relative";
        containerEl.innerHTML = `
            <div class="w-full h-full p-2 flex flex-col">
                <div class="flex items-center justify-between pb-1.5 text-xs text-gray-500 border-b border-gray-100 dark:border-white/5">
                    <span class="font-semibold uppercase tracking-wider">Touchpoint Pins</span>
                    <span class="text-3xs text-primary-500 font-mono">${pins.length} Location(s)</span>
                </div>
                <div id="${uniqueId}" class="w-full flex-1 min-h-[180px] rounded-lg overflow-hidden mt-1 relative z-0"></div>
            </div>
        `;

        const mapEl = containerEl.querySelector('#' + uniqueId);
        if (!mapEl) return;

        if (window.L) {
            const isDark = document.documentElement.classList.contains("dark");
            const defaultCenter = pins.length > 0 ? [pins[0].lat, pins[0].lng] : [20, 0];
            const defaultZoom = pins.length > 0 ? 4 : 2;

            const map = window.L.map(uniqueId, {
                center: defaultCenter,
                zoom: defaultZoom,
                scrollWheelZoom: false,
            });

            const tileUrl = isDark
                ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

            window.L.tileLayer(tileUrl, {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                maxZoom: 19
            }).addTo(map);

            const bounds = [];
            pins.forEach(pin => {
                if (pin.lat && pin.lng) {
                    const marker = window.L.circleMarker([pin.lat, pin.lng], {
                        radius: 6,
                        fillColor: '#3b82f6',
                        color: '#ffffff',
                        weight: 1.5,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(map);

                    marker.bindPopup(`
                        <div class="p-1 font-sans text-xs">
                            <div class="font-bold">${pin.title || 'Touchpoint'}</div>
                            ${pin.city ? `<div class="text-gray-500 text-3xs">${pin.city}${pin.country ? ', ' + pin.country : ''}</div>` : ''}
                            ${pin.value ? `<div class="text-primary-600 font-semibold mt-1">${pin.value}</div>` : ''}
                        </div>
                    `);
                    bounds.push([pin.lat, pin.lng]);
                }
            });

            if (bounds.length > 1) {
                map.fitBounds(bounds, { padding: [20, 20] });
            }

            this._chartInstances.set(containerEl, map);
        } else {
            mapEl.innerHTML = `<div class="flex items-center justify-center h-full text-xs text-gray-400">Leaflet library initializing...</div>`;
        }
    },
};
