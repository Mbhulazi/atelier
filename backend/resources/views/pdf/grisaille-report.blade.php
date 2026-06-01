<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grisaille Analysis Report</title>
    <style>
        body { font-family: 'Georgia', serif; margin: 40px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #d68222; padding-bottom: 20px; }
        .header h1 { font-size: 28px; margin: 0; color: #1a1a1a; }
        .header p { color: #666; margin-top: 8px; }
        .section { margin-bottom: 30px; }
        .section h2 { font-size: 18px; color: #d68222; border-bottom: 1px solid #e5e5e5; padding-bottom: 8px; }
        .values { display: flex; gap: 4px; margin: 15px 0; }
        .value-bar { flex: 1; height: 40px; display: flex; align-items: flex-end; justify-content: center; }
        .value-bar span { font-size: 10px; color: white; padding: 2px; }
        .palette-group { margin: 15px 0; padding: 15px; background: #fafaf7; border-radius: 8px; }
        .palette-group h3 { font-size: 14px; margin: 0 0 10px 0; }
        .color-swatch { display: inline-block; width: 24px; height: 24px; border-radius: 50%; border: 1px solid #ddd; margin-right: 8px; vertical-align: middle; }
        .color-name { font-size: 13px; }
        .glazing { margin: 10px 0; padding: 12px; border-left: 3px solid #d68222; background: #fafaf7; }
        .glazing h4 { font-size: 14px; margin: 0 0 6px 0; }
        .glazing p { font-size: 12px; margin: 4px 0; color: #666; }
        .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Grisaille Value Analysis</h1>
        <p>Generated on {{ $generated_at }} by Atelier</p>
    </div>

    <div class="section">
        <h2>Value Distribution</h2>
        <div class="values">
            @foreach($value_percentages as $i => $pct)
                <div class="value-bar" style="background: rgb({{ $i * 28 }}, {{ $i * 28 }}, {{ $i * 28 }}); height: {{ max(20, $pct * 200) }}px;">
                    <span>{{ $i }}</span>
                </div>
            @endforeach
        </div>
        <p style="font-size: 12px; color: #666;">
            Average value: {{ number_format($analysis->averageValue ?? 0, 1) }}
        </p>
    </div>

    <div class="section">
        <h2>Recommended Palette</h2>
        @foreach($palette as $group)
            <div class="palette-group">
                <h3>Values {{ $group['valueRange'] }} — {{ $group['label'] }}</h3>
                @foreach($group['colors'] as $color)
                    <span class="color-swatch" style="background: {{ $color['hex'] }}"></span>
                    <span class="color-name">{{ $color['name'] }} ({{ $color['pigment'] }})</span><br>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="section">
        <h2>Glazing Suggestions</h2>
        @foreach($glazings as $glaze)
            <div class="glazing">
                <h4>Value {{ $glaze['fromValue'] }} → {{ $glaze['toValue'] }}: {{ $glaze['glaze'] }}</h4>
                <p><strong>Medium:</strong> {{ $glaze['medium'] }}</p>
                <p><strong>Technique:</strong> {{ $glaze['technique'] }}</p>
                <p>{{ $glaze['description'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>Atelier — Professional Platform for Painters</p>
        <p>This analysis is a guide. Adjust based on your artistic vision.</p>
    </div>
</body>
</html>
