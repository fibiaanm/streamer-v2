import type { AvatarUrl } from '@/types'
import { useApi } from '@/lib/api'

export function useProfileApi() {
  const api = useApi()

  async function updateProfile(name: string): Promise<{ name: string; avatar_url: AvatarUrl }> {
    const res = await api.patch<{ data: { name: string; avatar_url: AvatarUrl } }>('/auth/profile', { name })
    return res.data.data
  }

  async function uploadAvatar(file: File): Promise<AvatarUrl> {
    const form = new FormData()
    form.append('avatar', file)
    const res = await api.post<{ data: { avatar_url: AvatarUrl } }>('/auth/profile/avatar', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return res.data.data.avatar_url
  }

  async function deleteAvatar(): Promise<{ avatar_url: AvatarUrl }> {
    const res = await api.delete<{ data: { avatar_url: AvatarUrl } }>('/auth/profile/avatar')
    return res.data.data
  }

  return { updateProfile, uploadAvatar, deleteAvatar }
}
