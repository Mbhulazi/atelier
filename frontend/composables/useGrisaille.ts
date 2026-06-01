export interface ValueAnalysis {
  valueMap: number[]
  valuePercentages: number[]
  totalPixels: number
  averageValue: number
}

export interface PaletteRecommendation {
  valueRange: string
  label: string
  colors: PaletteColor[]
}

export interface PaletteColor {
  name: string
  pigment: string
  hex: string
  valueRange: [number, number]
}

export interface GlazingSuggestion {
  fromValue: number
  toValue: number
  glaze: string
  medium: string
  technique: string
  description: string
}

export const useGrisaille = () => {
  const analysis = ref<ValueAnalysis | null>(null)
  const previewUrl = ref<string | null>(null)
  const processing = ref(false)

  const calculateLuminance = (r: number, g: number, b: number): number => {
    return 0.299 * r + 0.587 * g + 0.114 * b
  }

  const luminanceToValue = (luminance: number): number => {
    return Math.min(9, Math.floor(luminance / 25.6))
  }

  const analyzeImage = (imageData: ImageData): ValueAnalysis => {
    const pixels = imageData.data
    const valueMap = new Array(10).fill(0)
    const totalPixels = pixels.length / 4

    for (let i = 0; i < pixels.length; i += 4) {
      const r = pixels[i]
      const g = pixels[i + 1]
      const b = pixels[i + 2]
      const luminance = calculateLuminance(r, g, b)
      const value = luminanceToValue(luminance)
      valueMap[value]++
    }

    const valuePercentages = valueMap.map(count => count / totalPixels)
    const averageValue = valuePercentages.reduce(
      (sum, pct, i) => sum + pct * i, 0
    )

    return { valueMap, valuePercentages, totalPixels, averageValue }
  }

  const generatePreview = (imageData: ImageData): string => {
    const tempCanvas = document.createElement('canvas')
    tempCanvas.width = imageData.width
    tempCanvas.height = imageData.height
    const tempCtx = tempCanvas.getContext('2d')!

    const output = tempCtx.createImageData(imageData.width, imageData.height)
    const pixels = imageData.data

    for (let i = 0; i < pixels.length; i += 4) {
      const r = pixels[i]
      const g = pixels[i + 1]
      const b = pixels[i + 2]
      const luminance = calculateLuminance(r, g, b)
      const value = luminanceToValue(luminance)
      const gray = Math.round(value * 255 / 9)

      output.data[i] = gray
      output.data[i + 1] = gray
      output.data[i + 2] = gray
      output.data[i + 3] = 255
    }

    tempCtx.putImageData(output, 0, 0)
    return tempCanvas.toDataURL('image/png')
  }

  const generatePalette = (va: ValueAnalysis): PaletteRecommendation[] => {
    const { valuePercentages } = va

    const darkPigments: PaletteColor[] = [
      { name: 'Bone Black', pigment: 'PBk9', hex: '#1a1a1a', valueRange: [0, 2] },
      { name: 'Burnt Umber', pigment: 'PBr7', hex: '#3d2b1f', valueRange: [0, 2] },
      { name: 'Raw Umber', pigment: 'PBr7', hex: '#4a3728', valueRange: [0, 2] },
      { name: 'Van Dyke Brown', pigment: 'PBr7', hex: '#3e2723', valueRange: [0, 2] },
    ]

    const midtonePigments: PaletteColor[] = [
      { name: 'Yellow Ochre', pigment: 'PY43', hex: '#c8a951', valueRange: [3, 5] },
      { name: 'Cadmium Orange', pigment: 'PO20', hex: '#e87e04', valueRange: [3, 5] },
      { name: 'Transparent Oxide Red', pigment: 'PBr6', hex: '#8b4513', valueRange: [3, 5] },
      { name: 'Raw Sienna', pigment: 'PBr7', hex: '#a0522d', valueRange: [3, 5] },
    ]

    const lightPigments: PaletteColor[] = [
      { name: 'Titanium White + Cad Yellow', pigment: 'PW6 + PY35', hex: '#f5e6a3', valueRange: [6, 8] },
      { name: 'Titanium White + Yellow Ochre', pigment: 'PW6 + PY43', hex: '#e8d5a3', valueRange: [6, 8] },
      { name: 'Naples Yellow', pigment: 'PY41', hex: '#f0e4a0', valueRange: [6, 8] },
      { name: 'Buff Titanium', pigment: 'PBr24', hex: '#d4c5a0', valueRange: [6, 8] },
    ]

    const highlightPigments: PaletteColor[] = [
      { name: 'Titanium White', pigment: 'PW6', hex: '#f5f5f5', valueRange: [9, 9] },
      { name: 'Zinc White', pigment: 'PW4', hex: '#f0f0f0', valueRange: [9, 9] },
    ]

    const darkWeight = valuePercentages.slice(0, 3).reduce((a, b) => a + b, 0)
    const midWeight = valuePercentages.slice(3, 6).reduce((a, b) => a + b, 0)
    const lightWeight = valuePercentages.slice(6, 9).reduce((a, b) => a + b, 0)
    const highlightWeight = valuePercentages[9] || 0

    return [
      { valueRange: '0-2', label: `Darks (${Math.round(darkWeight * 100)}%)`, colors: darkPigments },
      { valueRange: '3-5', label: `Midtones (${Math.round(midWeight * 100)}%)`, colors: midtonePigments },
      { valueRange: '6-8', label: `Lights (${Math.round(lightWeight * 100)}%)`, colors: lightPigments },
      { valueRange: '9', label: `Highlights (${Math.round(highlightWeight * 100)}%)`, colors: highlightPigments },
    ]
  }

  const generateGlazings = (): GlazingSuggestion[] => [
    {
      fromValue: 0, toValue: 3,
      glaze: 'Diluted Burnt Umber',
      medium: 'Odorless Mineral Spirits + Linseed Oil (3:1)',
      technique: 'Thin wash',
      description: 'Establish dark foundations with transparent Burnt Umber wash. Keep thin to allow underpainting to show through.',
    },
    {
      fromValue: 3, toValue: 5,
      glaze: 'Yellow Ochre + Transparent Oxide',
      medium: 'Linseed Oil',
      technique: 'Scumbling',
      description: 'Build midtones with semi-opaque Yellow Ochre. Use dry brush technique for broken color effects.',
    },
    {
      fromValue: 5, toValue: 7,
      glaze: 'Cadmium Yellow + White',
      medium: 'Liquin or Stand Oil',
      technique: 'Wet glaze',
      description: 'Add warmth to lights with a thin Cad Yellow tint. Blend edges while wet for smooth transitions.',
    },
    {
      fromValue: 7, toValue: 9,
      glaze: 'Titanium White + touch of Yellow',
      medium: 'Stand Oil',
      technique: 'Scumble over dry',
      description: 'Final highlights with nearly pure Titanium White. Apply sparingly over dry underlayers.',
    },
  ]

  const loadImage = (file: File): Promise<HTMLImageElement> => {
    return new Promise((resolve, reject) => {
      const img = new Image()
      img.onload = () => resolve(img)
      img.onerror = reject
      img.src = URL.createObjectURL(file)
    })
  }

  const processImage = async (file: File) => {
    processing.value = true
    try {
      const img = await loadImage(file)
      const canvasEl = document.createElement('canvas')
      canvasEl.width = img.width
      canvasEl.height = img.height
      const canvasCtx = canvasEl.getContext('2d')!
      canvasCtx.drawImage(img, 0, 0)

      const imageData = canvasCtx.getImageData(0, 0, canvasEl.width, canvasEl.height)

      const valueAnalysis = analyzeImage(imageData)
      const preview = generatePreview(imageData)
      const palette = generatePalette(valueAnalysis)
      const glazings = generateGlazings()

      analysis.value = valueAnalysis
      previewUrl.value = preview

      return { analysis: valueAnalysis, preview, palette, glazings }
    } finally {
      processing.value = false
    }
  }

  return {
    analysis,
    previewUrl,
    processing,
    processImage,
  }
}
