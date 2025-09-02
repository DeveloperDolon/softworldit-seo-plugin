jQuery(function ($) {
  /* =========================================================
   Shared Helpers
========================================================= */
  const TR_WORDS = [
    "additionally",
    "after",
    "also",
    "although",
    "and",
    "because",
    "besides",
    "consequently",
    "despite",
    "eventually",
    "finally",
    "first",
    "for example",
    "for instance",
    "further",
    "furthermore",
    "hence",
    "however",
    "in addition",
    "in conclusion",
    "indeed",
    "instead",
    "last",
    "later",
    "likewise",
    "meanwhile",
    "moreover",
    "nevertheless",
    "next",
    "nonetheless",
    "nor",
    "now",
    "otherwise",
    "overall",
    "rather",
    "second",
    "similarly",
    "since",
    "so",
    "still",
    "subsequently",
    "then",
    "therefore",
    "third",
    "though",
    "thus",
    "to conclude",
    "to sum up",
    "whereas",
    "while",
    "yet",
  ];

  const normalize = (s) =>
    (s || "").toString().toLowerCase().replace(/\s+/g, " ").trim();
  const tokenize = (s) => normalize(s).match(/[a-z0-9’'-]+/gi) || [];

  function getEditorHTML() {
    if (window.wp && wp.data && wp.data.select) {
      try {
        return wp.data.select("core/editor").getEditedPostContent() || "";
      } catch (e) {}
    }
    const classic = document.getElementById("content");
    return classic ? classic.value : "";
  }
  function firstParagraph(html) {
    const m = html.match(/<p[^>]*>([\s\S]*?)<\/p>/i);
    if (m && m[1]) return $("<div>").html(m[1]).text();
    return $("<div>")
      .html(html)
      .text()
      .split(/[.!?]\s/)
      .slice(0, 2)
      .join(". ");
  }
  function subheads(html) {
    const arr = [];
    (html.match(/<(h2|h3)[^>]*>([\s\S]*?)<\/\1>/gi) || []).forEach((b) =>
      arr.push($("<div>").html(b).text())
    );
    return arr;
  }
  function syllables(w) {
    w = (w || "").toLowerCase();
    if (w.length <= 3) return 1;
    w = w.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, "").replace(/^y/, "");

    const m = w.match(/[aeiouy]{1,2}/g);
    return m ? m.length : 1;
  }
  function fre(text) {
    const s = text.split(/[.!?]+/).filter(Boolean);
    const words = tokenize(text);
    const syl = words.reduce((t, w) => t + syllables(w), 0);
    const S = s.length || 1,
      W = words.length || 1;
    const score = 206.835 - 1.015 * (W / S) - 84.6 * (syl / W);
    return { score, W, S, wps: W / S };
  }
  const passivePct = (text) => {
    const s = text.split(/[.!?]+/).filter(Boolean);
    if (!s.length) return 0;
    const re =
      /\b(am|is|are|was|were|be|been|being)\b\s+\b([a-z]+ed|written|given|made|seen|known|taken|driven|broken|built|done|read|told|won|lost|cut|bought|brought|felt)\b/gi;
    let p = 0;
    s.forEach((x) => {
      if (re.test(x.toLowerCase())) p++;
    });
    return (p / s.length) * 100;
  };
  const transitionPct = (text) => {
    const s = text.split(/[.!?]+/).filter(Boolean);
    if (!s.length) return 0;
    let c = 0;
    s.forEach((x) => {
      const t = normalize(x);
      if (
        TR_WORDS.some((w) => t.startsWith(w + " ") || t.includes(" " + w + " "))
      )
        c++;
    });
    return (c / s.length) * 100;
  };

  function containsKP(text, kp) {
    const tx = normalize(text),
      key = normalize(kp);
    if (!key) return false;
    if (tx.includes(key)) return true;
    const kpTokens = [...new Set(tokenize(kp))];
    const set = new Set(tokenize(text));
    const hit = kpTokens.filter((t) => set.has(t)).length;
    return (hit / (kpTokens.length || 1)) * 100 >= 60;
  }

  function setBadge(id, status, msg, overridden) {
    const $b = $("#ayc_" + id);
    if (!$b.length) return;
    $b.removeClass("good warn bad");
    if (status) $b.addClass(status);
    let label = "❌ Poor";
    if (status === "good") label = "✅ Good";
    else if (status === "warn") label = "⚠️ Check";
    $b.text(label);
    $("#ayc_" + id + "_msg").html(
      msg ? '<span class="ayc-note">💡 ' + msg + "</span>" : ""
    );
    $("#ayc_" + id + "_ovr").text(overridden ? AYC_SEO.i18n.overridden : "");
  }

  function quickMetaLengths(title, desc) {
    const $t = $("#ayc_analysis_title"),
      $d = $("#ayc_analysis_desc");
    if ($t.length)
      $t.text(
        title.length +
          " characters " +
          (title.length >= 50 && title.length <= 60 ? "✅ Good" : "⚠️ Check")
      );
    if ($d.length)
      $d.text(
        desc.length +
          " characters " +
          (desc.length >= 120 && desc.length <= 160 ? "✅ Good" : "⚠️ Check")
      );
  }

  /* =========================================================
   Editor screen: live analysis + small bar chart
========================================================= */
  let metricsChartEditor = null;
  function renderEditorChart(auto) {
    const canvas = document.getElementById("ayc_metrics_chart");
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    const labels = [],
      data = [],
      colors = [];
    Object.keys(auto).forEach((id) => {
      const [status] = auto[id];
      labels.push(id.replace(/_/g, " ").toUpperCase());
      data.push(status === "good" ? 3 : status === "warn" ? 2 : 1);
      colors.push(
        status === "good"
          ? "#28a745"
          : status === "warn"
          ? "#ffc107"
          : "#dc3545"
      );
    });
    if (metricsChartEditor) metricsChartEditor.destroy();
    metricsChartEditor = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            label: "Metric Status (3=Good,2=Check,1=Poor)",
            data,
            backgroundColor: colors,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { min: 0, max: 3, ticks: { stepSize: 1 } } },
      },
    });
  }

  function editorAutoAssess() {
    const $title = $("#ayc_meta_title"),
      $desc = $("#ayc_meta_description"),
      $kp = $("#ayc_focus_keyphrase");
    if (!$title.length || !$desc.length) return;
    const title = $title.val() || "",
      desc = $desc.val() || "",
      kp = $kp.val() || "",
      html = getEditorHTML();
    const text = $("<div>").html(html).text(),
      intro = firstParagraph(html),
      heads = subheads(html);

    quickMetaLengths(title, desc);

    const auto = {};
    auto.kw_in_title = kp
      ? containsKP(title, kp)
        ? ["good", "Keyphrase in title"]
        : ["bad", "Add keyphrase to title"]
      : ["warn", "Set focus keyphrase"];
    auto.kw_in_desc = kp
      ? containsKP(desc, kp)
        ? ["good", "Keyphrase in description"]
        : ["bad", "Add keyphrase to description"]
      : ["warn", "Set focus keyphrase"];
    auto.kw_in_first_para = kp
      ? containsKP(intro, kp)
        ? ["good", "Keyphrase in first paragraph"]
        : ["bad", "Add keyphrase to intro"]
      : ["warn", "Set focus keyphrase"];
    auto.kw_in_subhead = kp
      ? heads.some((h) => containsKP(h, kp))
        ? ["good", "Keyphrase appears in H2/H3"]
        : ["warn", "Consider adding keyphrase to H2/H3"]
      : ["warn", "Set focus keyphrase"];

    const words = tokenize(text),
      W = words.length || 1;
    const occ = kp
      ? (
          normalize(text).match(
            new RegExp(
              normalize(kp).replace(/[.*+?^${}()|[\]\\]/g, "\\$&"),
              "gi"
            )
          ) || []
        ).length
      : 0;
    const densityPct = kp ? (occ / W) * 100 : 0;
    let densStatus = "warn",
      densMsg = `Density ${densityPct.toFixed(2)}% (${occ} hits)`;
    if (!kp) densStatus = "warn";
    else if (densityPct === 0) densStatus = "bad";
    else if (densityPct < 0.3) densStatus = "warn";
    else if (densityPct <= 3) densStatus = "good";
    else (densStatus = "warn"), (densMsg += " (may be too high)");
    auto.kp_density = [densStatus, densMsg];

    const tmp = document.createElement("div");
    tmp.innerHTML = html;
    const a = Array.from(tmp.querySelectorAll("a[href]")),
      imgs = Array.from(tmp.querySelectorAll("img"));
    const host = AYC_SEO && AYC_SEO.host ? AYC_SEO.host : location.host || "";
    const internals = a.filter((el) => {
      const href = (el.getAttribute("href") || "").trim();
      if (!href || href.startsWith("#")) return false;
      if (
        href.startsWith("/") ||
        href.startsWith("./") ||
        href.startsWith("../")
      )
        return true;
      try {
        const u = new URL(href, location.origin);
        return u.host === host;
      } catch (e) {
        return false;
      }
    }).length;
    const externals = a.filter((el) => {
      const href = (el.getAttribute("href") || "").trim();
      if (!href || href.startsWith("#")) return false;
      try {
        const u = new URL(href, location.origin);
        return u.protocol.startsWith("http") && u.host && u.host !== host;
      } catch (e) {
        return false;
      }
    }).length;
    const totalImgs = imgs.length,
      withAlt = imgs.filter(
        (img) => (img.getAttribute("alt") || "").trim().length > 0
      ).length;
    auto.links_internal = [
      internals >= 1 ? "good" : "warn",
      `Internal links: ${internals}`,
    ];
    auto.links_external = [
      externals >= 1 ? "good" : "warn",
      `External links: ${externals}`,
    ];
    const altPct = totalImgs ? (withAlt / totalImgs) * 100 : 100;
    let altStatus = "good";
    if (totalImgs && altPct < 80) altStatus = "bad";
    else if (totalImgs && altPct < 100) altStatus = "warn";
    auto.image_alt = [
      altStatus,
      totalImgs
        ? `Alt coverage ${altPct.toFixed(0)}% (${withAlt}/${totalImgs})`
        : "No images",
    ];

    const F = fre(text),
      pass = passivePct(text),
      tr = transitionPct(text);
    auto.sentence_len =
      F.wps < 14
        ? ["good", `Avg sentence ≈ ${F.wps.toFixed(1)} words`]
        : ["warn", `Avg sentence ≈ ${F.wps.toFixed(1)} words`];
    auto.passive =
      pass <= 10
        ? ["good", `Passive ~${pass.toFixed(0)}%`]
        : ["warn", `Passive ~${pass.toFixed(0)}%`];
    auto.transition =
      tr >= 30
        ? ["good", `Transition ~${tr.toFixed(0)}% sentences`]
        : ["bad", `Transition ~${tr.toFixed(0)}% sentences`];
    const score = Math.max(-50, Math.min(120, F.score));
    auto.flesch =
      score >= 60
        ? ["good", `Flesch ${score.toFixed(0)} — Easy`]
        : ["warn", `Flesch ${score.toFixed(0)}`];

    Object.keys(auto).forEach((id) => {
      const [st, msg] = auto[id];
      const manual = ($("#ayc_" + id).data("manual-status") || "").toString();
      const manualStatus =
        manual === "good" || manual === "warn" || manual === "bad"
          ? manual
          : "";
      const note = $("#ayc_" + id + "_note").val() || "";
      if (manualStatus) {
        setBadge(id, manualStatus, note, true);
      } else {
        setBadge(id, st, msg, false);
      }
    });

    renderEditorChart(auto);
  }

  /* =========================================================
   Dashboard screen: dynamic charts (AJAX-powered)
========================================================= */
  let dashboardBar = null,
    donutCharts = {};

  function statusToScore(st) {
    return st === "good" ? 3 : st === "warn" ? 2 : 1;
  }
  function statusToColors(st) {
    return st === "good"
      ? ["#28a745", "#e0e0e0"]
      : st === "warn"
      ? ["#ffc107", "#e0e0e0"]
      : ["#dc3545", "#e0e0e0"];
  }

  function renderDashboardBar(agg, total) {
    const canvas = document.getElementById("ayc_metrics_chart");
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    const labels = [],
      data = [],
      colors = [];
    Object.keys(agg).forEach((k) => {
      const counts = agg[k];
      const score =
        (counts.good * 3 + counts.warn * 2 + counts.bad * 1) /
        Math.max(1, counts.good + counts.warn + counts.bad);
      labels.push(k.replace(/_/g, " ").toUpperCase());
      data.push(score);
      colors.push(
        score >= 2.5 ? "#28a745" : score >= 1.8 ? "#ffc107" : "#dc3545"
      );
    });
    if (dashboardBar) dashboardBar.destroy();
    dashboardBar = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            label: "Average Status (3=Good,2=Check,1=Poor)",
            data,
            backgroundColor: colors,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { min: 0, max: 3, ticks: { stepSize: 1 } } },
      },
    });
  }

  function renderDonuts(agg, total) {
    Object.keys(agg).forEach((id) => {
      const c = agg[id],
        goodPct = total ? Math.round((c.good / total) * 100) : 0;
      const st =
        c.good >= c.warn && c.good >= c.bad
          ? "good"
          : c.warn >= c.bad
          ? "warn"
          : "bad";
      const el = document.getElementById("chart_" + id);
      if (!el) return;
      if (donutCharts[id]) donutCharts[id].destroy();
      donutCharts[id] = new Chart(el, {
        type: "doughnut",
        data: {
          labels: ["Done", "Remaining"],
          datasets: [
            {
              data: [goodPct, 100 - goodPct],
              backgroundColor: statusToColors(st),
              borderWidth: 1,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          cutout: "70%",
        },
      });
      const center = document.getElementById("center_" + id);
      if (center) center.textContent = goodPct + "%";
    });
  }

  function fetchOverviewAndRender() {
    if (!$("#ayc-dashboard-root").length) return;
    $.post(
      AYC_SEO.ajax,
      { action: "ayc_metrics_overview", nonce: AYC_SEO.nonce, limit: 30 },
      function (res) {
        if (!res || !res.success) return;
        const total = res.data.count || 0;
        renderDashboardBar(res.data.agg, total);
        renderDonuts(res.data.agg, total);
      }
    );
  }

  $(document).on("click", ".ayc-save", function () {
    const id = $(this).data("id");
    const title = $('.ayc-meta-title[data-id="' + id + '"]').val();
    const desc = $('.ayc-meta-desc[data-id="' + id + '"]').val();
    const kp = $('.ayc-focus-kw[data-id="' + id + '"]').val();
    $.post(
      AYC_SEO.ajax,
      {
        action: "ayc_save_post_meta",
        nonce: AYC_SEO.nonce,
        post_id: id,
        meta_title: title,
        meta_desc: desc,
        focus_kw: kp,
      },
      function (resp) {
        if (resp && resp.success) {
          fetchOverviewAndRender();
        } else {
          alert("Save failed");
        }
      }
    );
  });

  editorAutoAssess();
  fetchOverviewAndRender();
//   setInterval(fetchOverviewAndRender, 9000);
});

// jQuery(document).ready(function ($) {
//   function updateAnalysis() {
//     var title = $("#ayc_meta_title").val();
//     console.log(title);
//     var desc = $("#ayc_meta_description").val();

//     var tLen = title.length;
//     var tMsg =
//       tLen >= 50 && tLen <= 60
//         ? "✅ Good"
//         : tLen > 60
//         ? "⚠️ Too long (50–60)"
//         : "⚠️ Too short (50–60)";
//     $("#ayc_analysis_title").text(tLen + " characters " + tMsg);

//     var dLen = desc.length;
//     var dMsg =
//       dLen >= 120 && dLen <= 160
//         ? "✅ Good"
//         : dLen > 160
//         ? "⚠️ Too long (120–160)"
//         : "⚠️ Too short (120–160)";
//     $("#ayc_analysis_desc").text(dLen + " characters " + dMsg);
//   }

//   $("#ayc_meta_title, #ayc_meta_description").on("input", updateAnalysis);
//   updateAnalysis();
// });
