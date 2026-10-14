import sys,cv2,numpy as np
s=cv2.imread(sys.argv[1]);hsv=cv2.cvtColor(s,cv2.COLOR_BGR2HSV)
bg=(hsv[...,1]<40)&(hsv[...,2]>140)
m=(~bg).astype(np.uint8)*255
m=cv2.morphologyEx(m,cv2.MORPH_OPEN,np.ones((9,9),np.uint8));m=cv2.morphologyEx(m,cv2.MORPH_CLOSE,np.ones((41,41),np.uint8))
n,l,st,_=cv2.connectedComponentsWithStats(m);keep=np.zeros_like(m)
for i in range(1,n):
  if st[i,4]>0.02*m.size: keep[l==i]=255
cs,_=cv2.findContours(keep,cv2.RETR_EXTERNAL,cv2.CHAIN_APPROX_SIMPLE);keep=np.zeros_like(m);cv2.drawContours(keep,cs,-1,255,-1)
keep=cv2.erode(keep,np.ones((15,15),np.uint8))  # stay inside fabric edge
cv2.imwrite(sys.argv[2],keep)
# placeholder backdrop made by OUR code (not AI) to dry-run the compositor
h,w=s.shape[:2];g=np.zeros_like(s);g[:]=(205,222,236);cv2.imwrite(sys.argv[3],g)
print(round(float((keep>0).mean()),4))
