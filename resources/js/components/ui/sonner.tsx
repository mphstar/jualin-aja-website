import {
  CircleCheckIcon,
  InfoIcon,
  Loader2Icon,
  OctagonXIcon,
  TriangleAlertIcon,
} from "lucide-react"
import { Toaster as Sonner, type ToasterProps } from "sonner"

// DISESUAIKAN dari bawaan shadcn: aslinya memakai `useTheme()` dari
// next-themes. Di aplikasi Vite tanpa <ThemeProvider>, hook itu selalu
// mengembalikan undefined sehingga toast terkunci di mode terang.
// Diarahkan ke store tema aplikasi ini.
import { useTemaStore } from "@/stores/temaStore"

const PETA_TEMA = {
  terang: "light",
  gelap: "dark",
  sistem: "system",
} as const

const Toaster = ({ ...props }: ToasterProps) => {
  const tema = useTemaStore((s) => s.tema)

  return (
    <Sonner
      theme={PETA_TEMA[tema]}
      className="toaster group"
      position="top-right"
      icons={{
        success: <CircleCheckIcon className="size-4" />,
        info: <InfoIcon className="size-4" />,
        warning: <TriangleAlertIcon className="size-4" />,
        error: <OctagonXIcon className="size-4" />,
        loading: <Loader2Icon className="size-4 animate-spin" />,
      }}
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
          "--border-radius": "var(--radius)",
        } as React.CSSProperties
      }
      {...props}
    />
  )
}

export { Toaster }
